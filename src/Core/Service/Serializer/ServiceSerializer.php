<?php

declare(strict_types=1);

namespace Aurora\Core\Service\Serializer;

use Aurora\Core\Service\Entity\ServiceInterface;

use const DATE_ATOM;

final readonly class ServiceSerializer
{
    /** @return array<string, mixed> */
    public function serialize(ServiceInterface $service): array
    {
        return [
            'id' => $service->getId(),
            'name' => $service->getName(),
            'createdAt' => $service->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
