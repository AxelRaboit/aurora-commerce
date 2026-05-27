<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultUserConfig\Serializer;

use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultUserConfigSerializerInterface::class)]
class VaultUserConfigSerializer implements VaultUserConfigSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(VaultUserConfigInterface $config): array
    {
        return [
            'argon2Salt' => $config->getArgon2Salt(),
        ];
    }
}
