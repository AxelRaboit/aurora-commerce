<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Serializer;

use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;

interface InvoiceSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(InvoiceInterface $invoice): array;

    /** @return array<string, mixed> */
    public function serializeDetail(InvoiceInterface $invoice): array;
}
