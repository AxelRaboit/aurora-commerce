<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Ocr\Contract\OllamaVisionClientInterface;
use Aurora\Module\Billing\Ocr\Dto\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Dto\InvoiceLineDraft;

use function is_array;
use function mb_strlen;

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
    private const array SCHEMA = [
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
            'supplier_website' => ['type' => ['string', 'null']],
            'supplier_legal_form' => ['type' => ['string', 'null'], 'description' => 'Legal form, e.g. SARL, SAS, SA, GmbH, Ltd'],
            'supplier_bank_name' => ['type' => ['string', 'null'], 'description' => 'Name of the bank for the supplier IBAN'],
            'buyer_name' => ['type' => ['string', 'null'], 'description' => 'Name of the buyer / bill-to company or person'],
            'buyer_vat_number' => ['type' => ['string', 'null'], 'description' => 'Buyer EU VAT number, e.g. FR12345678901'],
            'buyer_address' => ['type' => ['string', 'null'], 'description' => 'Full delivery or billing address of the buyer'],
            'buyer_country_code' => ['type' => ['string', 'null'], 'description' => 'ISO 3166-1 alpha-2 country code of the buyer'],
            'buyer_email' => ['type' => ['string', 'null']],
            'buyer_phone' => ['type' => ['string', 'null']],
            'invoice_number' => ['type' => ['string', 'null']],
            'reference' => ['type' => ['string', 'null'], 'description' => "Seller's own internal reference for this invoice"],
            'purchase_order_ref' => ['type' => ['string', 'null'], 'description' => 'Customer purchase order / order reference'],
            'issued_at' => ['type' => ['string', 'null'], 'description' => 'ISO date YYYY-MM-DD'],
            'due_at' => ['type' => ['string', 'null'], 'description' => 'ISO date YYYY-MM-DD'],
            'delivery_date' => ['type' => ['string', 'null'], 'description' => 'ISO date YYYY-MM-DD — actual delivery date'],
            'payment_terms' => ['type' => ['string', 'null'], 'description' => 'Free text, e.g. "30 jours fin de mois"'],
            'payment_method' => ['type' => ['string', 'null'], 'description' => 'Free text, e.g. virement, CB, chèque, prélèvement'],
            'currency' => ['type' => ['string', 'null'], 'description' => 'ISO 4217, e.g. EUR'],
            'incoterms' => ['type' => ['string', 'null'], 'description' => 'Incoterms rule, e.g. EXW, FOB, CIF, DDP'],
            'reverse_charge' => ['type' => ['boolean', 'null'], 'description' => 'True when VAT auto-liquidation (autoliquidation) applies'],
            'bank_details' => ['type' => ['string', 'null'], 'description' => 'Bank transfer details printed on the invoice (if different from supplier IBAN)'],
            'subtotal_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw amount string from the invoice (e.g. "1.200,00" or "2,290.00"). We convert to cents.'],
            'total_net_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw amount string from the invoice.'],
            'total_vat_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw amount string from the invoice.'],
            'total_gross_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw amount string from the invoice.'],
            'discount_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw discount/rebate amount string from the invoice (positive = deduction).'],
            'freight_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw freight/shipping amount string from the invoice.'],
            'insurance_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw insurance amount string from the invoice.'],
            'discount_rate_bp' => ['type' => ['integer', 'null'], 'description' => 'Global discount rate in basis points (e.g. 500 = 5%)'],
            'lines' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'label' => ['type' => 'string'],
                        'reference' => ['type' => ['string', 'null'], 'description' => "Supplier's article reference for this line"],
                        'description' => ['type' => ['string', 'null'], 'description' => 'Extended description (if longer than label)'],
                        'product_code' => ['type' => ['string', 'null'], 'description' => 'Product code / article reference on the line'],
                        'unit' => ['type' => ['string', 'null'], 'description' => 'pcs, kg, m, m2, h, l, ...'],
                        'quantity' => ['type' => ['string', 'null']],
                        'unit_price_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw unit price string from the invoice.'],
                        'discount_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw line discount string from the invoice.'],
                        'vat_rate_bp' => ['type' => ['integer', 'null']],
                        'total_net_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw line total (excl. VAT) string from the invoice.'],
                        'total_gross_cents' => ['type' => ['string', 'null'], 'description' => 'Copy the raw line total (incl. VAT) string from the invoice.'],
                        'origin' => ['type' => ['string', 'null'], 'description' => 'ISO 3166-1 alpha-2 country of origin (customs)'],
                    ],
                    'required' => ['label'],
                ],
            ],
            'confidence' => ['type' => 'number', 'description' => '0..1 self-rated confidence'],
            'uncertain_fields' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'List of SCHEMA KEY names (not label text!) where you returned a value but are not fully confident. Use the exact property names from this schema, e.g. ["supplier_email", "supplier_vat_number"]. Never use human-readable labels like "Email" or "TVA" — always the snake_case key. Only add a key when you DID return a value for it.',
            ],
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
            - Email: extract as-is if you can see it. Must contain "@". If you can only
              read part of it (e.g. the domain is blurry), still return what you can
              see and add "supplier_email" or "buyer_email" to uncertain_fields.
            - Country code: ISO 3166-1 alpha-2 (2 letters). Infer ONLY from clear signals
              (address country, "FR" prefix in VAT, IBAN country code). Otherwise null.
            - Set "confidence" based on overall legibility: 0.9+ for clean documents,
              0.7-0.9 for minor issues, 0.5-0.7 for several unclear fields.
              Only go below 0.5 if the document is fundamentally unreadable.
              Do NOT lower confidence just because some optional fields are absent.

            FORMAT RULES:
            - For all monetary amount fields (subtotal_cents, total_net_cents,
              total_vat_cents, total_gross_cents, discount_cents, freight_cents,
              insurance_cents, unit_price_cents, line total_net_cents, line
              total_gross_cents, line discount_cents): return the raw string
              EXACTLY as printed on the invoice — do NOT compute, do NOT convert,
              do NOT multiply. We parse it ourselves.
              Examples of what to copy verbatim:
                Invoice shows "19.90"        → return "19.90"
                Invoice shows "1.200.00"     → return "1.200.00"
                Invoice shows "2.290.00"     → return "2.290.00"
                Invoice shows "2,290.00"     → return "2,290.00"
                Invoice shows "1.200,00"     → return "1.200,00"
                Invoice shows "100.000,00"   → return "100.000,00"
              If the amount is absent or unreadable, return null.
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
            if (!is_array($row)) {
                continue;
            }

            if (!isset($row['label'])) {
                continue;
            }

            $lines[] = new InvoiceLineDraft(
                label: (string) $row['label'],
                productCode: $this->stringOrNull($row['product_code'] ?? null),
                unit: $this->stringOrNull($row['unit'] ?? null),
                quantity: $this->stringOrNull($row['quantity'] ?? null),
                unitPriceCents: $this->parseMoneyCents($row['unit_price_cents'] ?? null),
                vatRateBp: $this->intOrNullSafe($row['vat_rate_bp'] ?? null),
                totalNetCents: $this->parseMoneyCents($row['total_net_cents'] ?? null),
                totalGrossCents: $this->parseMoneyCents($row['total_gross_cents'] ?? null),
                reference: $this->stringOrNull($row['reference'] ?? null),
                description: $this->stringOrNull($row['description'] ?? null),
                discountCents: $this->parseMoneyCents($row['discount_cents'] ?? null),
                origin: $this->normalizeCountryCode($row['origin'] ?? null),
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
            supplierWebsite: $this->stringOrNull($payload['supplier_website'] ?? null),
            supplierLegalForm: $this->stringOrNull($payload['supplier_legal_form'] ?? null),
            supplierBankName: $this->stringOrNull($payload['supplier_bank_name'] ?? null),
            buyerName: $this->stringOrNull($payload['buyer_name'] ?? null),
            buyerVatNumber: $this->stringOrNull($payload['buyer_vat_number'] ?? null),
            buyerAddress: $this->stringOrNull($payload['buyer_address'] ?? null),
            buyerCountryCode: $this->normalizeCountryCode($payload['buyer_country_code'] ?? null),
            buyerEmail: $this->stringOrNull($payload['buyer_email'] ?? null),
            buyerPhone: $this->stringOrNull($payload['buyer_phone'] ?? null),
            invoiceNumber: $this->stringOrNull($payload['invoice_number'] ?? null),
            reference: $this->stringOrNull($payload['reference'] ?? null),
            purchaseOrderRef: $this->stringOrNull($payload['purchase_order_ref'] ?? null),
            issuedAt: $this->dateOrNullSafe($payload['issued_at'] ?? null),
            dueAt: $this->dateOrNullSafe($payload['due_at'] ?? null),
            deliveryDate: $this->dateOrNullSafe($payload['delivery_date'] ?? null),
            paymentTerms: $this->stringOrNull($payload['payment_terms'] ?? null),
            paymentMethod: $this->stringOrNull($payload['payment_method'] ?? null),
            currency: $this->stringOrNull($payload['currency'] ?? null),
            incoterms: $this->stringOrNull($payload['incoterms'] ?? null),
            reverseCharge: isset($payload['reverse_charge']) ? (bool) $payload['reverse_charge'] : null,
            bankDetails: $this->stringOrNull($payload['bank_details'] ?? null),
            subtotalCents: $this->parseMoneyCents($payload['subtotal_cents'] ?? null),
            totalNetCents: $this->parseMoneyCents($payload['total_net_cents'] ?? null),
            totalVatCents: $this->parseMoneyCents($payload['total_vat_cents'] ?? null),
            totalGrossCents: $this->parseMoneyCents($payload['total_gross_cents'] ?? null),
            discountCents: $this->parseMoneyCents($payload['discount_cents'] ?? null),
            freightCents: $this->parseMoneyCents($payload['freight_cents'] ?? null),
            insuranceCents: $this->parseMoneyCents($payload['insurance_cents'] ?? null),
            discountRateBp: $this->intOrNullSafe($payload['discount_rate_bp'] ?? null),
            lines: $lines,
            confidence: (float) ($payload['confidence'] ?? 0.0),
            uncertainFields: array_values(array_filter(
                (array) ($payload['uncertain_fields'] ?? []),
                static fn (mixed $v): bool => is_string($v) && '' !== $v,
            )),
        );
    }

    /**
     * Parse a monetary string (as copied verbatim from the invoice) into integer cents.
     * Mirrors the JS parseMoney() util: separator followed by 1-2 digits at the end
     * is the decimal separator; separator followed by 3 digits is a thousands separator.
     *
     * Also handles the fallback where the model returned a numeric value instead of a
     * string: a non-integer float is treated as a euro amount (multiply by 100); a plain
     * integer is trusted as-is (the model may have already computed cents correctly).
     */
    private function parseMoneyCents(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        // Model returned a plain number instead of the requested string.
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            // Non-integer float → model returned euros → multiply.
            if (abs($value - round($value)) > 0.001) {
                return (int) round($value * 100);
            }

            return (int) $value;
        }

        if (!is_string($value) || '' === mb_trim($value)) {
            return null;
        }

        $str = mb_trim($value);

        // Strip a trailing currency symbol/code if present (e.g. "2.290,00 EUR").
        $str = preg_replace('/\s*[A-Z€£$]{1,3}\s*$/u', '', $str) ?? $str;
        $str = mb_trim($str);

        // Detect decimal separator: a separator followed by 1-2 digits at the end.
        if (preg_match('/[.,](\d{1,2})$/', $str, $m)) {
            $decLen = mb_strlen($m[1]);
            $intPart = preg_replace('/[.,]/', '', mb_substr($str, 0, -$decLen - 1)) ?? '';
            $normalized = $intPart.'.'.$m[1];
        } else {
            // No decimal part — strip all separators.
            $normalized = preg_replace('/[.,]/', '', $str) ?? $str;
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        return (int) round((float) $normalized * 100);
    }

    private function normalizeCountryCode(mixed $value): ?string
    {
        $countryCode = $this->stringOrNull($value);
        if (null === $countryCode) {
            return null;
        }

        $countryCode = mb_strtoupper($countryCode);

        return preg_match('/^[A-Z]{2}$/', $countryCode) ? $countryCode : null;
    }
}
