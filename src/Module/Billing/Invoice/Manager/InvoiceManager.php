<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Invoice\Contract\InvoiceManagerInterface;
use Aurora\Module\Billing\Invoice\Contract\TiersManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Contract\OcrJobManagerInterface;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Throwable;

#[AsAlias(InvoiceManagerInterface::class)]
final readonly class InvoiceManager implements InvoiceManagerInterface
{
    use ScalarCoercionTrait;

    /** @var array<string, callable> */
    private array $fieldSetters;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
        private TiersManagerInterface $tiersManager,
        private OcrJobManagerInterface $ocrJobManager,
        private InvoiceRepository $invoiceRepository,
        private SettingRepository $settingRepository,
        private LoggerInterface $logger,
    ) {
        $this->fieldSetters = [
            'number' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setNumber($this->stringOrNull($value)),
            'supplierNumber' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setSupplierNumber($this->stringOrNull($value)),
            'purchaseOrderRef' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setPurchaseOrderRef($this->stringOrNull($value)),
            'paymentMethod' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setPaymentMethod($this->stringOrNull($value)),
            'paymentTerms' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setPaymentTerms($this->stringOrNull($value)),
            'issuedAt' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setIssuedAt($this->dateOrNull($value, 'admin.billing.invoices.update.invalidDate')),
            'dueAt' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setDueAt($this->dateOrNull($value, 'admin.billing.invoices.update.invalidDate')),
            'subtotalCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setSubtotalCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'totalNetCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setTotalNetCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'totalVatCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setTotalVatCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'totalGrossCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setTotalGrossCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'discountCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setDiscountCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'freightCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setFreightCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'insuranceCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setInsuranceCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'discountRateBp' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setDiscountRateBp($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'reference' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setReference($this->stringOrNull($value)),
            'project' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setProject($this->stringOrNull($value)),
            'incoterms' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setIncoterms($this->stringOrNull($value)),
            'deliveryDate' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setDeliveryDate($this->dateOrNull($value, 'admin.billing.invoices.update.invalidDate')),
            'reverseCharge' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setReverseCharge(null !== $value ? (bool) $value : null),
            'bankDetails' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setBankDetails($this->stringOrNull($value)),
        ];
    }

    public function validate(Invoice $invoice): void
    {
        // Always generate an internal sequential number — independent of supplier's number.
        $prefix = $this->settingRepository->get(
            ApplicationParameterEnum::BillingInvoicePrefix->value,
            SequencePrefixEnum::Invoice->value,
        );
        if (null !== $prefix && '' !== $prefix && null === $invoice->getNumber()) {
            $year = (int) ($invoice->getIssuedAt() ?? new DateTimeImmutable())->format('Y');
            $invoice->setNumber($this->invoiceRepository->getNextNumber($prefix, $year));
        }

        $invoice->setStatus(InvoiceStatusEnum::Validated);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.validated', 'Invoice', $invoice->getId(), [
            'number' => $invoice->getNumber(),
        ]);
    }

    public function delete(Invoice $invoice, bool $deleteTiers = false, bool $deleteBuyer = false): void
    {
        $invoice->assertEditable();

        $id = $invoice->getId();
        $number = $invoice->getNumber();
        $ocrJob = $invoice->getOcrJob();
        $tiers = $deleteTiers ? $invoice->getTiers() : null;
        $buyer = $deleteBuyer ? $invoice->getBuyerTiers() : null;

        $this->entityManager->remove($invoice);
        $this->entityManager->flush();

        // Everything after flush() is best-effort. Any failure here must NOT
        // bubble up to the controller — the invoice is already removed from the DB
        // and a jsonFailure response would leave the frontend stuck on a dead page.
        try {
            if ($ocrJob instanceof OcrJob) {
                $this->ocrJobManager->delete($ocrJob);
            }

            if ($tiers instanceof Tiers) {
                $this->tiersManager->delete($tiers);
            }

            if ($buyer instanceof Tiers) {
                $this->tiersManager->delete($buyer);
            }

            $this->auditLogger->log('billing', 'invoice.deleted', 'Invoice', $id, [
                'number' => $number,
                'tiersDeleted' => $tiers instanceof Tiers,
                'buyerDeleted' => $buyer instanceof Tiers,
            ]);
        } catch (Throwable $throwable) {
            $this->logger->error('Invoice post-delete cleanup failed', [
                'invoiceId' => $id,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    public function createCreditNote(Invoice $invoice, ?string $reason = null): Invoice
    {
        if (!$invoice->getStatus()->canHaveCreditNote()) {
            throw new InvalidArgumentException('admin.billing.invoices.creditNote.invalidStatus');
        }

        if ($invoice->isCancelled()) {
            throw new InvalidArgumentException('admin.billing.invoices.creditNote.alreadyCancelled');
        }

        $cn = new Invoice();
        $cn->setStatus(InvoiceStatusEnum::CreditNote);

        $cnPrefix = $this->settingRepository->get(
            ApplicationParameterEnum::BillingCreditNotePrefix->value,
            SequencePrefixEnum::CreditNote->value,
        ) ?? SequencePrefixEnum::CreditNote->value;
        $cn->setNumber($cnPrefix.'-'.($invoice->getNumber() ?? $invoice->getId()));
        $cn->setSupplierNumber($invoice->getSupplierNumber());
        $cn->setTiers($invoice->getTiers());
        $cn->setBuyerTiers($invoice->getBuyerTiers());
        $cn->setSubtotalCents(null !== $invoice->getSubtotalCents() ? -$invoice->getSubtotalCents() : null);
        $cn->setDiscountCents($invoice->getDiscountCents());
        $cn->setFreightCents($invoice->getFreightCents());
        $cn->setInsuranceCents($invoice->getInsuranceCents());
        $cn->setDiscountRateBp($invoice->getDiscountRateBp());
        $cn->setIncoterms($invoice->getIncoterms());
        $cn->setReverseCharge($invoice->getReverseCharge());
        $cn->setBankDetails($invoice->getBankDetails());
        $cn->setCurrency($invoice->getCurrency());
        $cn->setIssuedAt(new DateTimeImmutable());
        $cn->setTotalNetCents(null !== $invoice->getTotalNetCents() ? -$invoice->getTotalNetCents() : null);
        $cn->setTotalVatCents(null !== $invoice->getTotalVatCents() ? -$invoice->getTotalVatCents() : null);
        $cn->setTotalGrossCents(null !== $invoice->getTotalGrossCents() ? -$invoice->getTotalGrossCents() : null);

        if (null !== $reason && '' !== $reason) {
            $cn->setNotes($reason);
        }

        foreach ($invoice->getLines() as $line) {
            $cnLine = new InvoiceLine();
            $cnLine->setLabel($line->getLabel());
            $cnLine->setProductCode($line->getProductCode());
            $cnLine->setUnit($line->getUnit());
            $cnLine->setQuantity($line->getQuantity());
            $cnLine->setUnitPriceCents(null !== $line->getUnitPriceCents() ? -$line->getUnitPriceCents() : null);
            $cnLine->setVatRateBp($line->getVatRateBp());
            $cnLine->setTotalNetCents(null !== $line->getTotalNetCents() ? -$line->getTotalNetCents() : null);
            $cnLine->setTotalGrossCents(null !== $line->getTotalGrossCents() ? -$line->getTotalGrossCents() : null);
            $cnLine->setReference($line->getReference());
            $cnLine->setDescription($line->getDescription());
            $cnLine->setDiscountCents(null !== $line->getDiscountCents() ? -$line->getDiscountCents() : null);
            $cnLine->setOrigin($line->getOrigin());
            $cnLine->setPosition($line->getPosition());
            $cn->addLine($cnLine);
            $this->entityManager->persist($cnLine);
        }

        $invoice->setCreditNote($cn);

        $this->entityManager->persist($cn);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.credit_note_created', 'Invoice', $invoice->getId(), [
            'creditNoteId' => $cn->getId(),
            'reason' => $reason,
        ]);

        return $cn;
    }

    public function updateField(Invoice $invoice, string $field, mixed $value): void
    {
        $invoice->assertEditable();

        $setter = $this->fieldSetters[$field] ?? null;
        if (null === $setter) {
            throw new InvalidArgumentException('admin.billing.invoices.update.unknownField');
        }

        $setter($invoice, $value);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.updated', 'Invoice', $invoice->getId(), [
            'field' => $field,
        ]);
    }

    public function createFromOcrDraft(InvoiceDraft $draft, OcrJob $job): Invoice
    {
        $invoice = new Invoice();
        $invoice->setOcrJob($job);
        $invoice->setDocument($job->getMedia());
        $invoice->setStatus(InvoiceStatusEnum::NeedsReview);
        $invoice->setSupplierNumber($draft->invoiceNumber);
        $invoice->setPurchaseOrderRef($draft->purchaseOrderRef);
        $invoice->setIssuedAt($draft->issuedAt);
        $invoice->setDueAt($draft->dueAt);
        $invoice->setPaymentTerms($draft->paymentTerms);
        $invoice->setPaymentMethod($draft->paymentMethod);
        $invoice->setCurrency($this->resolveCurrency($draft->currency));
        $invoice->setSubtotalCents($draft->subtotalCents);
        $invoice->setTotalNetCents($draft->totalNetCents);
        $invoice->setTotalVatCents($draft->totalVatCents);
        $invoice->setTotalGrossCents($draft->totalGrossCents);
        $invoice->setDiscountCents($draft->discountCents);
        $invoice->setFreightCents($draft->freightCents);
        $invoice->setInsuranceCents($draft->insuranceCents);
        $invoice->setDiscountRateBp($draft->discountRateBp);
        $invoice->setReference($draft->reference);
        $invoice->setIncoterms($draft->incoterms);
        $invoice->setDeliveryDate($draft->deliveryDate);
        $invoice->setReverseCharge($draft->reverseCharge);
        $invoice->setBankDetails($draft->bankDetails);
        $invoice->setTiers($this->tiersManager->findOrCreateSupplierFromDraft($draft));
        $invoice->setBuyerTiers($this->tiersManager->findOrCreateClientFromDraft($draft));

        $position = 0;
        foreach ($draft->lines as $lineDraft) {
            $line = new InvoiceLine();
            $line->setLabel($lineDraft->label);
            $line->setProductCode($lineDraft->productCode);
            $line->setUnit($lineDraft->unit);
            $line->setQuantity($lineDraft->quantity ?? '1.0000');
            $line->setUnitPriceCents($lineDraft->unitPriceCents);
            $line->setVatRateBp($lineDraft->vatRateBp);
            $line->setTotalNetCents($lineDraft->totalNetCents);
            $line->setTotalGrossCents($lineDraft->totalGrossCents);
            $line->setReference($lineDraft->reference);
            $line->setDescription($lineDraft->description);
            $line->setDiscountCents($lineDraft->discountCents);
            $line->setOrigin($lineDraft->origin);
            $line->setPosition($position++);
            $invoice->addLine($line);
        }

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.created_from_ocr', 'Invoice', $invoice->getId(), [
            'jobId' => $job->getId(),
            'confidence' => $draft->confidence,
        ]);

        return $invoice;
    }

    public function updateFromOcrDraft(Invoice $invoice, InvoiceDraft $draft, OcrJob $job): void
    {
        // Always update supplier number from fresh OCR data
        $invoice->setSupplierNumber($draft->invoiceNumber);

        $invoice->setPurchaseOrderRef($draft->purchaseOrderRef);
        $invoice->setIssuedAt($draft->issuedAt);
        $invoice->setDueAt($draft->dueAt);
        $invoice->setPaymentTerms($draft->paymentTerms);
        $invoice->setPaymentMethod($draft->paymentMethod);
        $invoice->setCurrency($this->resolveCurrency($draft->currency));
        $invoice->setSubtotalCents($draft->subtotalCents);
        $invoice->setTotalNetCents($draft->totalNetCents);
        $invoice->setTotalVatCents($draft->totalVatCents);
        $invoice->setTotalGrossCents($draft->totalGrossCents);
        $invoice->setDiscountCents($draft->discountCents);
        $invoice->setFreightCents($draft->freightCents);
        $invoice->setInsuranceCents($draft->insuranceCents);
        $invoice->setDiscountRateBp($draft->discountRateBp);
        $invoice->setReference($draft->reference);
        $invoice->setIncoterms($draft->incoterms);
        $invoice->setDeliveryDate($draft->deliveryDate);
        $invoice->setReverseCharge($draft->reverseCharge);
        $invoice->setBankDetails($draft->bankDetails);
        $invoice->setTiers($this->tiersManager->findOrCreateSupplierFromDraft($draft));
        $invoice->setBuyerTiers($this->tiersManager->findOrCreateClientFromDraft($draft));

        // Rebuild lines from scratch
        foreach ($invoice->getLines()->toArray() as $line) {
            $invoice->removeLine($line);
            $this->entityManager->remove($line);
        }

        $position = 0;
        foreach ($draft->lines as $lineDraft) {
            $line = new InvoiceLine();
            $line->setLabel($lineDraft->label);
            $line->setProductCode($lineDraft->productCode);
            $line->setUnit($lineDraft->unit);
            $line->setQuantity($lineDraft->quantity ?? '1.0000');
            $line->setUnitPriceCents($lineDraft->unitPriceCents);
            $line->setVatRateBp($lineDraft->vatRateBp);
            $line->setTotalNetCents($lineDraft->totalNetCents);
            $line->setTotalGrossCents($lineDraft->totalGrossCents);
            $line->setReference($lineDraft->reference);
            $line->setDescription($lineDraft->description);
            $line->setDiscountCents($lineDraft->discountCents);
            $line->setOrigin($lineDraft->origin);
            $line->setPosition($position++);
            $invoice->addLine($line);
        }

        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.rescanned', 'Invoice', $invoice->getId(), [
            'jobId' => $job->getId(),
            'confidence' => $draft->confidence,
        ]);
    }

    private function resolveCurrency(?string $code): CurrencyEnum
    {
        if (null === $code) {
            return CurrencyEnum::EUR;
        }

        return CurrencyEnum::tryFrom(mb_strtoupper($code)) ?? CurrencyEnum::EUR;
    }
}
