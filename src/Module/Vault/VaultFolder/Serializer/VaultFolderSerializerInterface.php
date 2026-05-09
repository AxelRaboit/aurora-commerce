<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Serializer;

use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;

interface VaultFolderSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultFolderInterface $folder): array;
}
