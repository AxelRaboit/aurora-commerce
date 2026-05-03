<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Contract;

use Aurora\Module\Billing\Invoice\Entity\Supplier;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use InvalidArgumentException;

interface SupplierManagerInterface
{
    public function delete(Supplier $supplier): void;

    /**
     * Inline-edit a single whitelisted field.
     *
     * @throws InvalidArgumentException with a translation key when the field
     *                                  name is unknown or the value is invalid
     */
    public function updateField(Supplier $supplier, string $field, mixed $value): void;

    /**
     * Resolve an existing supplier (by VAT number, then by name) or persist a
     * new one populated from the OCR draft. Returns null if no name was found
     * (a supplier without a name would violate the NOT NULL constraint).
     */
    public function findOrCreateFromDraft(InvoiceDraft $draft): ?Supplier;
}
