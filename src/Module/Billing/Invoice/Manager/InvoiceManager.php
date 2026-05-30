<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface;
use Aurora\Module\Billing\Invoice\Entity\TiersInterface;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Dto\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Dto\InvoiceLineDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Billing\Ocr\Manager\OcrJobManagerInterface;
use Aurora\Module\Billing\Setting\BillingSettingEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Money\Enum\CurrencyEnum;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Throwable;

#[AsAlias(InvoiceManagerInterface::class)]
class InvoiceManager implements InvoiceManagerInterface
{
    use ScalarCoercionTrait;

    /** @var array<string, callable> */
    protected readonly array $fieldSetters;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly TiersManagerInterface $tiersManager,
        protected readonly OcrJobManagerInterface $ocrJobManager,
        protected readonly InvoiceRepository $invoiceRepository,
        protected readonly SettingRepository $settingRepository,
        protected readonly LoggerInterface $logger,
    ) {
        $this->fieldSetters = [
            'number' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setNumber($this->stringOrNull($value)),
            'supplierNumber' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setSupplierNumber($this->stringOrNull($value)),
            'purchaseOrderRef' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setPurchaseOrderRef($this->stringOrNull($value)),
            'paymentMethod' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setPaymentMethod($this->stringOrNull($value)),
            'paymentTerms' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setPaymentTerms($this->stringOrNull($value)),
            'issuedAt' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setIssuedAt($this->dateOrNull($value, 'backend.billing.invoices.update.invalid_date')),
            'dueAt' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setDueAt($this->dateOrNull($value, 'backend.billing.invoices.update.invalid_date')),
            'subtotalCents' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setSubtotalCents($this->intOrNull($value, 'backend.billing.invoices.update.not_numeric')),
            'totalNetCents' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setTotalNetCents($this->intOrNull($value, 'backend.billing.invoices.update.not_numeric')),
            'totalVatCents' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setTotalVatCents($this->intOrNull($value, 'backend.billing.invoices.update.not_numeric')),
            'totalGrossCents' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setTotalGrossCents($this->intOrNull($value, 'backend.billing.invoices.update.not_numeric')),
            'discountCents' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setDiscountCents($this->intOrNull($value, 'backend.billing.invoices.update.not_numeric')),
            'freightCents' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setFreightCents($this->intOrNull($value, 'backend.billing.invoices.update.not_numeric')),
            'insuranceCents' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setInsuranceCents($this->intOrNull($value, 'backend.billing.invoices.update.not_numeric')),
            'discountRateBp' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setDiscountRateBp($this->intOrNull($value, 'backend.billing.invoices.update.not_numeric')),
            'reference' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setReference($this->stringOrNull($value)),
            'project' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setProject($this->stringOrNull($value)),
            'incoterms' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setIncoterms($this->stringOrNull($value)),
            'deliveryDate' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setDeliveryDate($this->dateOrNull($value, 'backend.billing.invoices.update.invalid_date')),
            'reverseCharge' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setReverseCharge(null !== $value ? (bool) $value : null),
            'bankDetails' => fn (InvoiceInterface $invoice, mixed $value): InvoiceInterface => $invoice->setBankDetails($this->stringOrNull($value)),
        ];
    }

    public function validate(InvoiceInterface $invoice): void
    {
        // Always generate an internal sequential number — independent of supplier's number.
        // Empty prefix = admin opted out (no auto-numbering).
        $prefix = $this->settingRepository->getOrDefault(BillingSettingEnum::InvoicePrefix);
        if ('' !== $prefix && null === $invoice->getNumber()) {
            $year = (int) ($invoice->getIssuedAt() ?? new DateTimeImmutable())->format('Y');
            $invoice->setNumber($this->invoiceRepository->getNextNumber($prefix, $year));
        }

        $invoice->setStatus(InvoiceStatusEnum::Validated);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.validated', 'Invoice', $invoice->getId(), $this->auditPayload($invoice));
    }

    public function delete(InvoiceInterface $invoice, bool $deleteTiers = false, bool $deleteBuyer = false): void
    {
        $invoice->assertEditable();

        $id = $invoice->getId();
        $payload = $this->auditPayload($invoice);
        $ocrJob = $invoice->getOcrJob();
        $tiers = $deleteTiers ? $invoice->getTiers() : null;
        $buyer = $deleteBuyer ? $invoice->getBuyerTiers() : null;

        $this->entityManager->remove($invoice);
        $this->entityManager->flush();

        // Everything after flush() is best-effort. Any failure here must NOT
        // bubble up to the controller — the invoice is already removed from the DB
        // and a jsonFailure response would leave the frontend stuck on a dead page.
        try {
            if ($ocrJob instanceof OcrJobInterface) {
                $this->ocrJobManager->delete($ocrJob);
            }

            if ($tiers instanceof TiersInterface) {
                $this->tiersManager->delete($tiers);
            }

            if ($buyer instanceof TiersInterface) {
                $this->tiersManager->delete($buyer);
            }

            $this->auditLogger->log('billing', 'invoice.deleted', 'Invoice', $id, [
                ...$payload,
                'tiersDeleted' => $tiers instanceof TiersInterface,
                'buyerDeleted' => $buyer instanceof TiersInterface,
            ]);
        } catch (Throwable $throwable) {
            $this->logger->error('Invoice post-delete cleanup failed', [
                'invoiceId' => $id,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    public function createCreditNote(InvoiceInterface $invoice, ?string $reason = null): InvoiceInterface
    {
        if (!$invoice->getStatus()->canHaveCreditNote()) {
            throw new InvalidArgumentException('backend.billing.invoices.credit_note.invalid_status');
        }

        if ($invoice->isCancelled()) {
            throw new InvalidArgumentException('backend.billing.invoices.credit_note.already_cancelled');
        }

        $cn = $this->createInvoice();
        $cn->setStatus(InvoiceStatusEnum::CreditNote);

        $cnPrefix = $this->settingRepository->getOrDefault(BillingSettingEnum::CreditNotePrefix);
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
            $cnLine = $this->createInvoiceLine();
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
            ...$this->auditPayload($invoice),
            'creditNoteId' => $cn->getId(),
            'reason' => $reason,
        ]);

        return $cn;
    }

    public function updateField(InvoiceInterface $invoice, string $field, mixed $value): void
    {
        $invoice->assertEditable();

        $setter = $this->fieldSetters[$field] ?? null;
        if (null === $setter) {
            throw new InvalidArgumentException('backend.billing.invoices.update.unknown_field');
        }

        $setter($invoice, $value);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.updated', 'Invoice', $invoice->getId(), [
            ...$this->auditPayload($invoice),
            'field' => $field,
        ]);
    }

    public function createFromOcrDraft(InvoiceDraft $draft, OcrJobInterface $job): InvoiceInterface
    {
        $invoice = $this->createInvoice();
        $invoice->setOcrJob($job);
        // Same GED Document as the OcrJob — single file, single storage,
        // single audit trail. The Document's status can transition to
        // `Published` later once the invoice is validated.
        $invoice->setDocument($job->getDocument());
        $invoice->setStatus(InvoiceStatusEnum::NeedsReview);
        $invoice->setSupplierNumber($draft->invoiceNumber);
        $this->applyDraft($invoice, $draft);

        $position = 0;
        foreach ($draft->lines as $lineDraft) {
            $line = $this->createInvoiceLine();
            $this->applyLineDraft($line, $lineDraft, $position++);
            $invoice->addLine($line);
        }

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.created_from_ocr', 'Invoice', $invoice->getId(), [
            ...$this->auditPayload($invoice),
            'jobId' => $job->getId(),
            'confidence' => $draft->confidence,
        ]);

        return $invoice;
    }

    public function updateFromOcrDraft(InvoiceInterface $invoice, InvoiceDraft $draft, OcrJobInterface $job): void
    {
        // Always update supplier number from fresh OCR data
        $invoice->setSupplierNumber($draft->invoiceNumber);
        $this->applyDraft($invoice, $draft);

        // Rebuild lines from scratch
        foreach ($invoice->getLines()->toArray() as $line) {
            $invoice->removeLine($line);
            $this->entityManager->remove($line);
        }

        $position = 0;
        foreach ($draft->lines as $lineDraft) {
            $line = $this->createInvoiceLine();
            $this->applyLineDraft($line, $lineDraft, $position++);
            $invoice->addLine($line);
        }

        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.rescanned', 'Invoice', $invoice->getId(), [
            ...$this->auditPayload($invoice),
            'jobId' => $job->getId(),
            'confidence' => $draft->confidence,
        ]);
    }

    protected function createInvoice(): InvoiceInterface
    {
        return new Invoice();
    }

    protected function createInvoiceLine(): InvoiceLineInterface
    {
        return new InvoiceLine();
    }

    /**
     * Base payload for every Invoice audit entry. Override to add custom fields.
     *
     * Note: Invoice has no standard create/update/delete triplet — its
     * lifecycle uses domain events (validated, deleted, updated:field,
     * created_from_ocr, rescanned, credit_note_created). Each event splat-
     * merges the payload to stay extensible.
     */
    protected function auditPayload(InvoiceInterface $invoice): array
    {
        return ['number' => $invoice->getNumber()];
    }

    protected function applyDraft(InvoiceInterface $invoice, InvoiceDraft $draft): void
    {
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
    }

    protected function applyLineDraft(InvoiceLineInterface $line, InvoiceLineDraft $lineDraft, int $position): void
    {
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
        $line->setPosition($position);
    }

    private function resolveCurrency(?string $code): CurrencyEnum
    {
        if (null === $code) {
            return CurrencyEnum::EUR;
        }

        return CurrencyEnum::tryFrom(mb_strtoupper($code)) ?? CurrencyEnum::EUR;
    }
}
