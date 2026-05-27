<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultUserConfig\Dto;

interface VaultUserConfigInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): VaultUserConfigInputInterface;
}
