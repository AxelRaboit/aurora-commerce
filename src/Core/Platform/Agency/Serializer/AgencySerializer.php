<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Agency\Serializer;

use Aurora\Core\Platform\Agency\Entity\AgencyInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use const DATE_ATOM;

#[AsAlias(AgencySerializerInterface::class)]
class AgencySerializer implements AgencySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AgencyInterface $agency): array
    {
        return [
            'id' => $agency->getId(),
            'name' => $agency->getName(),
            'createdAt' => $agency->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
