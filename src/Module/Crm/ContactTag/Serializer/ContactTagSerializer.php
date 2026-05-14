<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Serializer;

use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ContactTagSerializerInterface::class)]
class ContactTagSerializer implements ContactTagSerializerInterface
{
    public function serialize(ContactTagInterface $contactTag): array
    {
        return [
            'id' => $contactTag->getId(),
            'label' => $contactTag->getLabel(),
            'slug' => $contactTag->getSlug(),
            'color' => $contactTag->getColor(),
        ];
    }
}
