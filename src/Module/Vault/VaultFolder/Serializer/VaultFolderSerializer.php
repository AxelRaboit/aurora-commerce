<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Serializer;

use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultFolderSerializerInterface::class)]
class VaultFolderSerializer implements VaultFolderSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultFolderInterface $folder): array
    {
        return [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'color' => $folder->getColor(),
            'position' => $folder->getPosition(),
            'parentId' => $folder->getParent()?->getId(),
            'createdAt' => $folder->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $folder->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
