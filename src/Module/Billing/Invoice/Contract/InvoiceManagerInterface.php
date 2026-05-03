<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Contract;

use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;

interface InvoiceManagerInterface
{
    /** Switch the invoice to the Validated status. */
    public function validate(Invoice $invoice): void;

    /** Permanently delete an invoice (and its lines, via cascade). */
    public function delete(Invoice $invoice): void;

    /**
     * Inline-edit a single whitelisted field.
     *
     * @throws \InvalidArgumentException with a translation key when the field
     *                                   name is unknown or the value is invalid
     */
    public function updateField(Invoice $invoice, string $field, mixed $value): void;

    /**
     * Persist a NeedsReview Invoice (with lines) populated from an OCR draft
     * and link it to the originating job. Resolves/creates the supplier through
     * SupplierManagerInterface — no direct EntityManager calls leak elsewhere.
     */
    public function createFromOcrDraft(InvoiceDraft $draft, OcrJob $job): Invoice;
}
