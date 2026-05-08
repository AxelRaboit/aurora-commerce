<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface;
use InvalidArgumentException;

interface InvoiceLineManagerInterface
{
    /** Append a blank line at the end of the invoice and return it. */
    public function add(InvoiceInterface $invoice): InvoiceLineInterface;

    /**
     * Inline-edit a single whitelisted line field.
     *
     * @throws InvalidArgumentException with a translation key
     */
    public function updateField(InvoiceLineInterface $line, string $field, mixed $value): void;

    public function delete(InvoiceLineInterface $line): void;
}
