<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultFolder\Dto;

interface VaultFolderInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): VaultFolderInputInterface;
}
