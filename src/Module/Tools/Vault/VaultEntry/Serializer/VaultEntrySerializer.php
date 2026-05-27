<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultEntry\Serializer;

use Aurora\Module\Tools\Vault\VaultEntry\Entity\VaultEntryInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultEntrySerializerInterface::class)]
class VaultEntrySerializer implements VaultEntrySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultEntryInterface $entry): array
    {
        return [
            'id' => $entry->getId(),
            'type' => $entry->getType()->value,
            'title' => $entry->getTitle(),
            'url' => $entry->getUrl(),
            'encryptedData' => $entry->getEncryptedData(),
            'iv' => $entry->getIv(),
            'isFavorite' => $entry->isFavorite(),
            'folderId' => $entry->getFolder()?->getId(),
            'folderName' => $entry->getFolder()?->getName(),
            'folderColor' => $entry->getFolder()?->getColor(),
            'createdAt' => $entry->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $entry->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
