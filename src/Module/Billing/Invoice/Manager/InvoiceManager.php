<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Invoice\Contract\InvoiceManagerInterface;
use Aurora\Module\Billing\Invoice\Contract\SupplierManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(InvoiceManagerInterface::class)]
final readonly class InvoiceManager implements InvoiceManagerInterface
{
    use ScalarCoercionTrait;

    /** @var array<string, callable> */
    private array $fieldSetters;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
        private SupplierManagerInterface $supplierManager,
    ) {
        $this->fieldSetters = [
            'number' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setNumber($this->stringOrNull($value)),
            'purchaseOrderRef' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setPurchaseOrderRef($this->stringOrNull($value)),
            'paymentMethod' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setPaymentMethod($this->stringOrNull($value)),
            'paymentTerms' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setPaymentTerms($this->stringOrNull($value)),
            'issuedAt' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setIssuedAt($this->dateOrNull($value, 'admin.billing.invoices.update.invalidDate')),
            'dueAt' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setDueAt($this->dateOrNull($value, 'admin.billing.invoices.update.invalidDate')),
            'totalNetCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setTotalNetCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'totalVatCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setTotalVatCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'totalGrossCents' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setTotalGrossCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'buyerName' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setBuyerName($this->stringOrNull($value)),
            'buyerVatNumber' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setBuyerVatNumber($this->stringOrNull($value)),
            'buyerAddress' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setBuyerAddress($this->stringOrNull($value)),
            'buyerCountryCode' => fn (Invoice $invoice, mixed $value): Invoice => $invoice->setBuyerCountryCode($this->stringOrNull($value)),
        ];
    }

    public function validate(Invoice $invoice): void
    {
        $invoice->setStatus(InvoiceStatusEnum::Validated);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.validated', 'Invoice', $invoice->getId(), [
            'number' => $invoice->getNumber(),
        ]);
    }

    public function delete(Invoice $invoice): void
    {
        $invoice->assertEditable();

        $id = $invoice->getId();
        $number = $invoice->getNumber();

        $this->entityManager->remove($invoice);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.deleted', 'Invoice', $id, [
            'number' => $number,
        ]);
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
        $invoice->setNumber($draft->invoiceNumber);
        $invoice->setPurchaseOrderRef($draft->purchaseOrderRef);
        $invoice->setIssuedAt($draft->issuedAt);
        $invoice->setDueAt($draft->dueAt);
        $invoice->setPaymentTerms($draft->paymentTerms);
        $invoice->setPaymentMethod($draft->paymentMethod);
        $invoice->setCurrency($this->resolveCurrency($draft->currency));
        $invoice->setTotalNetCents($draft->totalNetCents);
        $invoice->setTotalVatCents($draft->totalVatCents);
        $invoice->setTotalGrossCents($draft->totalGrossCents);
        $invoice->setSupplier($this->supplierManager->findOrCreateFromDraft($draft));
        $invoice->setBuyerName($draft->buyerName);
        $invoice->setBuyerVatNumber($draft->buyerVatNumber);
        $invoice->setBuyerAddress($draft->buyerAddress);
        $invoice->setBuyerCountryCode($draft->buyerCountryCode);

        $position = 0;
        foreach ($draft->lines as $lineDraft) {
            $line = new InvoiceLine();
            $line->setLabel($lineDraft->label);
            $line->setSku($lineDraft->sku);
            $line->setUnit($lineDraft->unit);
            $line->setQuantity($lineDraft->quantity ?? '1.0000');
            $line->setUnitPriceCents($lineDraft->unitPriceCents);
            $line->setVatRateBp($lineDraft->vatRateBp);
            $line->setTotalNetCents($lineDraft->totalNetCents);
            $line->setTotalGrossCents($lineDraft->totalGrossCents);
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

    private function resolveCurrency(?string $code): CurrencyEnum
    {
        if (null === $code) {
            return CurrencyEnum::EUR;
        }

        return CurrencyEnum::tryFrom(mb_strtoupper($code)) ?? CurrencyEnum::EUR;
    }
}
