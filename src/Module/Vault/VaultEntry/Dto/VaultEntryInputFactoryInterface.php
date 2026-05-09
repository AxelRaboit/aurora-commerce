<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Dto;

interface VaultEntryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): VaultEntryInputInterface;
}
