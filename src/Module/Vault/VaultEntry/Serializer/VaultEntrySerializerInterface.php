<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Serializer;

use Aurora\Module\Vault\VaultEntry\Entity\VaultEntryInterface;

interface VaultEntrySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultEntryInterface $entry): array;
}
