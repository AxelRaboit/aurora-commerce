<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultUserConfig\Dto;

interface VaultUserConfigInputInterface
{
    public function getArgon2Salt(): string;
}
