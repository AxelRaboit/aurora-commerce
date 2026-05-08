<?php

declare(strict_types=1);

namespace Aurora\Core\Service\Serializer;

use Aurora\Core\Service\Entity\ServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use const DATE_ATOM;

#[AsAlias(ServiceSerializerInterface::class)]
class ServiceSerializer implements ServiceSerializerInterface
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
