<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultFolder\Serializer;

use Aurora\Module\Tools\Vault\VaultFolder\Entity\VaultFolderInterface;

interface VaultFolderSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultFolderInterface $folder): array;
}
