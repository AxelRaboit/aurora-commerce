<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultUserConfig\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class VaultUserConfigInput implements VaultUserConfigInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'vault.config.errors.salt_required')]
        #[Assert\Length(min: 16, max: 128)]
        public readonly string $argon2Salt = '',
    ) {}

    public function getArgon2Salt(): string
    {
        return $this->argon2Salt;
    }
}
