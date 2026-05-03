<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Contract;

use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use InvalidArgumentException;

interface InvoiceLineManagerInterface
{
    /** Append a blank line at the end of the invoice and return it. */
    public function add(Invoice $invoice): InvoiceLine;

    /**
     * Inline-edit a single whitelisted line field.
     *
     * @throws InvalidArgumentException with a translation key
     */
    public function updateField(InvoiceLine $line, string $field, mixed $value): void;

    public function delete(InvoiceLine $line): void;
}
