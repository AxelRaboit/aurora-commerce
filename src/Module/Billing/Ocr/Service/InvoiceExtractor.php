<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Ocr\Contract\OllamaVisionClientInterface;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Aurora\Module\Billing\Ocr\DTO\InvoiceLineDraft;

/**
 * Turns (raw OCR text + invoice image) into a structured InvoiceDraft via
 * a vision-capable LLM. The schema is enforced by Ollama's `format` field
 * so the model output is always parseable.
 *
 * Money is always emitted in cents (integers) and VAT rate as basis points
 * (e.g. 2000 = 20.00%) — no floats for currency, and no locale-specific
 * decimal parsing on the PHP side.
 */
final readonly class InvoiceExtractor
{
    use ScalarCoercionTrait;

    /**
     * JSON Schema sent as Ollama's `format`. Keep keys flat snake_case —
     * that's what the model will be asked to produce.
     */
    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'supplier_name' => ['type' => ['string', 'null']],
            'supplier_vat_number' => ['type' => ['string', 'null'], 'description' => 'EU VAT number, e.g. FR12345678901'],
            'supplier_registration_number' => ['type' => ['string', 'null'], 'description' => 'Company registration ID (SIRET in FR = 14 digits, SIREN = 9, etc.)'],
            'supplier_iban' => ['type' => ['string', 'null']],
            'supplier_bic' => ['type' => ['string', 'null'], 'description' => 'SWIFT/BIC code, 8 or 11 alphanumeric chars'],
            'supplier_email' => ['type' => ['string', 'null']],
            'supplier_phone' => ['type' => ['string', 'null']],
            'supplier_address' => ['type' => ['string', 'null']],
            'supplier_country_code' => ['type' => ['string', 'null'], 'description' => 'ISO 3166-1 alpha-2, e.g. FR, BE, ES'],
            'invoice_number' => ['type' => ['string', 'null']],
            'purchase_order_ref' => ['type' => ['string', 'null'], 'description' => 'Customer purchase order / order reference'],
            'issued_at' => ['type' => ['string', 'null'], 'description' => 'ISO date YYYY-MM-DD'],
            'due_at' => ['type' => ['string', 'null'], 'description' => 'ISO date YYYY-MM-DD'],
            'payment_terms' => ['type' => ['string', 'null'], 'description' => 'Free text, e.g. "30 jours fin de mois"'],
            'payment_method' => ['type' => ['string', 'null'], 'description' => 'Free text, e.g. virement, CB, chèque, prélèvement'],
            'currency' => ['type' => ['string', 'null'], 'description' => 'ISO 4217, e.g. EUR'],
            'total_net_cents' => ['type' => ['integer', 'null']],
            'total_vat_cents' => ['type' => ['integer', 'null']],
            'total_gross_cents' => ['type' => ['integer', 'null']],
            'lines' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'label' => ['type' => 'string'],
                        'sku' => ['type' => ['string', 'null'], 'description' => 'Product code/reference'],
                        'unit' => ['type' => ['string', 'null'], 'description' => 'pcs, kg, m, m2, h, l, ...'],
                        'quantity' => ['type' => ['string', 'null']],
                        'unit_price_cents' => ['type' => ['integer', 'null']],
                        'vat_rate_bp' => ['type' => ['integer', 'null']],
                        'total_net_cents' => ['type' => ['integer', 'null']],
                        'total_gross_cents' => ['type' => ['integer', 'null']],
                    ],
                    'required' => ['label'],
                ],
            ],
            'confidence' => ['type' => 'number', 'description' => '0..1 self-rated confidence'],
        ],
        'required' => ['lines', 'confidence'],
    ];

    public function __construct(private OllamaVisionClientInterface $ollama) {}

    public function extract(string $imageAbsolutePath, string $rawOcrText): InvoiceDraft
    {
        $prompt = $this->buildPrompt($rawOcrText);
        $result = $this->ollama->generateStructured($prompt, $imageAbsolutePath, self::SCHEMA);

        return $this->hydrate($result['response']);
    }

    private function buildPrompt(string $ocrText): string
    {
        $truncated = mb_substr($ocrText, 0, 6000);

        return <<<PROMPT
You are an expert at reading supplier invoices. The user has provided one
invoice document as both an image and as text extracted by an OCR engine.

Respond with ONE JSON object matching the provided schema. No prose, no
Markdown fences, no commentary — only the JSON.

CRITICAL ANTI-HALLUCINATION RULES:
- ONLY return values you can literally SEE on the image OR find in the OCR text.
- If a value is not present or unreadable, return null. NEVER guess, NEVER infer.
- Do NOT invent: invoice numbers, dates, IBAN/BIC, VAT numbers, addresses.
- An IBAN MUST start with 2 letters (country code) + 2 digits + the rest. If
  what you see does not match this format, return null for the IBAN.
- A French VAT number starts with "FR" + 11 digits/letters. Validate before returning.
- SIRET = exactly 14 digits, SIREN = exactly 9 digits. If the visible identifier
  doesn't match either length, return null (do not pad / truncate).
- BIC = 8 or 11 alphanumeric characters (no spaces). Otherwise null.
- Email: must contain "@" and a "." after it. Otherwise null.
- Country code: ISO 3166-1 alpha-2 (2 letters). Infer ONLY from clear signals
  (address country, "FR" prefix in VAT, IBAN country code). Otherwise null.
- Lower "confidence" below 0.6 if ANY field had to be guessed or was ambiguous.

FORMAT RULES:
- All monetary amounts MUST be integers in CENTS (multiply by 100). Never
  return floats. Example: 19.90 EUR -> 1990.
- VAT rate MUST be integer basis points (20% -> 2000, 5.5% -> 550).
- Dates MUST be ISO format YYYY-MM-DD, copied from what's actually written.
- Currency is ISO 4217 (EUR, USD, GBP, CHF...). Use EUR only if the symbol/code
  is visible on the document.
- "lines" is the list of invoice line items (one per physical line item).
  Skip subtotals and tax-summary rows.
- "confidence" is YOUR self-assessment between 0.0 and 1.0 of how well the
  data was readable. Be HONEST: prefer null + low confidence over invention.

OCR text (best-effort, may contain errors):
---
{$truncated}
---

Now look at the image and produce the JSON.
PROMPT;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrate(array $payload): InvoiceDraft
    {
        $lines = [];
        foreach ($payload['lines'] ?? [] as $row) {
            if (!\is_array($row) || !isset($row['label'])) {
                continue;
            }
            $lines[] = new InvoiceLineDraft(
                label: (string) $row['label'],
                sku: $this->stringOrNull($row['sku'] ?? null),
                unit: $this->stringOrNull($row['unit'] ?? null),
                quantity: $this->stringOrNull($row['quantity'] ?? null),
                unitPriceCents: $this->intOrNullSafe($row['unit_price_cents'] ?? null),
                vatRateBp: $this->intOrNullSafe($row['vat_rate_bp'] ?? null),
                totalNetCents: $this->intOrNullSafe($row['total_net_cents'] ?? null),
                totalGrossCents: $this->intOrNullSafe($row['total_gross_cents'] ?? null),
            );
        }

        return new InvoiceDraft(
            supplierName: $this->stringOrNull($payload['supplier_name'] ?? null),
            supplierVatNumber: $this->stringOrNull($payload['supplier_vat_number'] ?? null),
            supplierRegistrationNumber: $this->stringOrNull($payload['supplier_registration_number'] ?? null),
            supplierIban: $this->stringOrNull($payload['supplier_iban'] ?? null),
            supplierBic: $this->stringOrNull($payload['supplier_bic'] ?? null),
            supplierEmail: $this->stringOrNull($payload['supplier_email'] ?? null),
            supplierPhone: $this->stringOrNull($payload['supplier_phone'] ?? null),
            supplierAddress: $this->stringOrNull($payload['supplier_address'] ?? null),
            supplierCountryCode: $this->normalizeCountryCode($payload['supplier_country_code'] ?? null),
            invoiceNumber: $this->stringOrNull($payload['invoice_number'] ?? null),
            purchaseOrderRef: $this->stringOrNull($payload['purchase_order_ref'] ?? null),
            issuedAt: $this->dateOrNullSafe($payload['issued_at'] ?? null),
            dueAt: $this->dateOrNullSafe($payload['due_at'] ?? null),
            paymentTerms: $this->stringOrNull($payload['payment_terms'] ?? null),
            paymentMethod: $this->stringOrNull($payload['payment_method'] ?? null),
            currency: $this->stringOrNull($payload['currency'] ?? null),
            totalNetCents: $this->intOrNullSafe($payload['total_net_cents'] ?? null),
            totalVatCents: $this->intOrNullSafe($payload['total_vat_cents'] ?? null),
            totalGrossCents: $this->intOrNullSafe($payload['total_gross_cents'] ?? null),
            lines: $lines,
            confidence: (float) ($payload['confidence'] ?? 0.0),
        );
    }

    private function normalizeCountryCode(mixed $value): ?string
    {
        $countryCode = $this->stringOrNull($value);
        if (null === $countryCode) {
            return null;
        }
        $countryCode = strtoupper($countryCode);

        return preg_match('/^[A-Z]{2}$/', $countryCode) ? $countryCode : null;
    }

}
