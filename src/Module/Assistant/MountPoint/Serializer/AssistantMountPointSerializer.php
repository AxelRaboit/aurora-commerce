<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Serializer;

use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AssistantMountPointSerializerInterface::class)]
class AssistantMountPointSerializer implements AssistantMountPointSerializerInterface
{
    public function serialize(AssistantMountPointInterface $mountPoint): array
    {
        return [
            'id' => $mountPoint->getId(),
            'name' => $mountPoint->getName(),
            'path' => $mountPoint->getPath(),
            'access' => $mountPoint->getAccess()->value,
            'active' => $mountPoint->isActive(),
            'createdAt' => $mountPoint->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $mountPoint->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
