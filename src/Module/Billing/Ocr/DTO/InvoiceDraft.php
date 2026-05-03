<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\DTO;

/**
 * Structured payload produced by the OCR pipeline. Mirrors the JSON schema
 * sent to the vision model (see InvoiceExtractor::SCHEMA). All fields are
 * nullable because real-world invoices vary; downstream code decides which
 * absences flag the job as needing human review.
 */
final readonly class InvoiceDraft
{
    private const TOTAL_TOLERANCE_CENTS = 200;
    private const CONFIDENCE_THRESHOLD = 0.85;

    /**
     * @param list<InvoiceLineDraft> $lines
     */
    public function __construct(
        public ?string $supplierName,
        public ?string $supplierVatNumber,
        public ?string $supplierRegistrationNumber,
        public ?string $supplierIban,
        public ?string $supplierBic,
        public ?string $supplierEmail,
        public ?string $supplierPhone,
        public ?string $supplierAddress,
        public ?string $supplierCountryCode,
        public ?string $invoiceNumber,
        public ?string $purchaseOrderRef,
        public ?\DateTimeImmutable $issuedAt,
        public ?\DateTimeImmutable $dueAt,
        public ?string $paymentTerms,
        public ?string $paymentMethod,
        public ?string $currency,
        public ?int $totalNetCents,
        public ?int $totalVatCents,
        public ?int $totalGrossCents,
        public array $lines,
        public float $confidence,
    ) {}

    /**
     * Quality gate used to decide whether the extracted draft can be flagged
     * Completed (auto-trust) or NeedsReview. Pure function — no I/O.
     */
    public function isTrustworthy(): bool
    {
        if ($this->confidence < self::CONFIDENCE_THRESHOLD) {
            return false;
        }
        if (null !== $this->totalNetCents && null !== $this->totalVatCents && null !== $this->totalGrossCents) {
            $expected = $this->totalNetCents + $this->totalVatCents;
            if (abs($expected - $this->totalGrossCents) > self::TOTAL_TOLERANCE_CENTS) {
                return false;
            }
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'supplier_name' => $this->supplierName,
            'supplier_vat_number' => $this->supplierVatNumber,
            'supplier_registration_number' => $this->supplierRegistrationNumber,
            'supplier_iban' => $this->supplierIban,
            'supplier_bic' => $this->supplierBic,
            'supplier_email' => $this->supplierEmail,
            'supplier_phone' => $this->supplierPhone,
            'supplier_address' => $this->supplierAddress,
            'supplier_country_code' => $this->supplierCountryCode,
            'invoice_number' => $this->invoiceNumber,
            'purchase_order_ref' => $this->purchaseOrderRef,
            'issued_at' => $this->issuedAt?->format('Y-m-d'),
            'due_at' => $this->dueAt?->format('Y-m-d'),
            'payment_terms' => $this->paymentTerms,
            'payment_method' => $this->paymentMethod,
            'currency' => $this->currency,
            'total_net_cents' => $this->totalNetCents,
            'total_vat_cents' => $this->totalVatCents,
            'total_gross_cents' => $this->totalGrossCents,
            'lines' => array_map(static fn (InvoiceLineDraft $line): array => [
                'label' => $line->label,
                'sku' => $line->sku,
                'unit' => $line->unit,
                'quantity' => $line->quantity,
                'unit_price_cents' => $line->unitPriceCents,
                'vat_rate_bp' => $line->vatRateBp,
                'total_net_cents' => $line->totalNetCents,
                'total_gross_cents' => $line->totalGrossCents,
            ], $this->lines),
            'confidence' => $this->confidence,
        ];
    }
}
