<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Serializer;

use Aurora\Core\Agency\Entity\AgencyInterface;

use const DATE_ATOM;

final readonly class AgencySerializer
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
