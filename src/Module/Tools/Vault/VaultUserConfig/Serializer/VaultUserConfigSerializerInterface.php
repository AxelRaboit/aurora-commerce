<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultUserConfig\Serializer;

use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;

interface VaultUserConfigSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultUserConfigInterface $config): array;
}
