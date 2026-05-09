<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultFolderInputFactoryInterface::class)]
class VaultFolderInputFactory implements VaultFolderInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): VaultFolderInputInterface
    {
        return new VaultFolderInput(
            name: Str::trimFromArray($data, 'name'),
            color: Str::trimOrNullFromArray($data, 'color'),
            position: isset($data['position']) ? (int) $data['position'] : 0,
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
        );
    }
}
