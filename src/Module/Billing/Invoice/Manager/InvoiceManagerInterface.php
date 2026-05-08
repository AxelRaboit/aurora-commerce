<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Ocr\Dto\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use InvalidArgumentException;

interface InvoiceManagerInterface
{
    /** Switch the invoice to the Validated status. */
    public function validate(InvoiceInterface $invoice): void;

    /** Permanently delete an invoice (and its lines, via cascade). Optionally also deletes the linked supplier and/or buyer tiers. */
    public function delete(InvoiceInterface $invoice, bool $deleteTiers = false, bool $deleteBuyer = false): void;

    /**
     * Inline-edit a single whitelisted field.
     *
     * @throws InvalidArgumentException with a translation key when the field
     *                                  name is unknown or the value is invalid
     */
    public function updateField(InvoiceInterface $invoice, string $field, mixed $value): void;

    /**
     * Persist a NeedsReview Invoice (with lines) populated from an OCR draft
     * and link it to the originating job. Resolves/creates the tiers (supplier/client) through
     * TiersManagerInterface — no direct EntityManager calls leak elsewhere.
     */
    public function createFromOcrDraft(InvoiceDraft $draft, OcrJobInterface $job): InvoiceInterface;

    /**
     * Re-apply an OCR draft onto an existing editable invoice.
     * Lines are cleared and rebuilt from the draft; the invoice number is
     * preserved if already set (user may have edited it).
     */
    public function updateFromOcrDraft(InvoiceInterface $invoice, InvoiceDraft $draft, OcrJobInterface $job): void;

    /**
     * Issue a credit note (avoir) that cancels the given validated/paid invoice.
     * The credit note is a new Invoice with negated amounts, status CreditNote,
     * and a bi-directional link to the original.
     *
     * @throws InvalidArgumentException if the invoice cannot be credited
     */
    public function createCreditNote(InvoiceInterface $invoice, ?string $reason = null): InvoiceInterface;
}
