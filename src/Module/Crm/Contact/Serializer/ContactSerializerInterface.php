<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Serializer;

use Aurora\Module\Crm\Contact\Entity\ContactInterface;

interface ContactSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ContactInterface $contact): array;
}
