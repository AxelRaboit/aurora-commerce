<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultEntry\Serializer;

use Aurora\Module\Tools\Vault\VaultEntry\Entity\VaultEntryInterface;

interface VaultEntrySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultEntryInterface $entry): array;
}
