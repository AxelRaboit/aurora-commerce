<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultUserConfig\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultUserConfigInputFactoryInterface::class)]
class VaultUserConfigInputFactory implements VaultUserConfigInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): VaultUserConfigInputInterface
    {
        return new VaultUserConfigInput(
            argon2Salt: Str::trimFromArray($data, 'argon2Salt'),
        );
    }
}
