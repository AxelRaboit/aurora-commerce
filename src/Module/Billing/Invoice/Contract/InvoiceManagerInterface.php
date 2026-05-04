<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Contract;

use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use InvalidArgumentException;

interface InvoiceManagerInterface
{
    /** Switch the invoice to the Validated status. */
    public function validate(Invoice $invoice): void;

    /** Permanently delete an invoice (and its lines, via cascade). Optionally also deletes the linked supplier and/or buyer tiers. */
    public function delete(Invoice $invoice, bool $deleteTiers = false, bool $deleteBuyer = false): void;

    /**
     * Inline-edit a single whitelisted field.
     *
     * @throws InvalidArgumentException with a translation key when the field
     *                                  name is unknown or the value is invalid
     */
    public function updateField(Invoice $invoice, string $field, mixed $value): void;

    /**
     * Persist a NeedsReview Invoice (with lines) populated from an OCR draft
     * and link it to the originating job. Resolves/creates the tiers (supplier/client) through
     * TiersManagerInterface — no direct EntityManager calls leak elsewhere.
     */
    public function createFromOcrDraft(InvoiceDraft $draft, OcrJob $job): Invoice;

    /**
     * Re-apply an OCR draft onto an existing editable invoice.
     * Lines are cleared and rebuilt from the draft; the invoice number is
     * preserved if already set (user may have edited it).
     */
    public function updateFromOcrDraft(Invoice $invoice, InvoiceDraft $draft, OcrJob $job): void;

    /**
     * Issue a credit note (avoir) that cancels the given validated/paid invoice.
     * The credit note is a new Invoice with negated amounts, status CreditNote,
     * and a bi-directional link to the original.
     *
     * @throws InvalidArgumentException if the invoice cannot be credited
     */
    public function createCreditNote(Invoice $invoice, ?string $reason = null): Invoice;
}
