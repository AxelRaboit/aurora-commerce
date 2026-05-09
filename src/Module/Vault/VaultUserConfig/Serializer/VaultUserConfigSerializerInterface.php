<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultUserConfig\Serializer;

use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;

interface VaultUserConfigSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultUserConfigInterface $config): array;
}
