<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Serializer;

use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;

interface ContactTagSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ContactTagInterface $contactTag): array;
}
