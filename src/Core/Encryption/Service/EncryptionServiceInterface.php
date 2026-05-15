<?php

declare(strict_types=1);

namespace Aurora\Core\Encryption\Service;

use SensitiveParameter;

interface EncryptionServiceInterface
{
    public function encrypt(#[SensitiveParameter] string $plaintext): string;

    public function decrypt(#[SensitiveParameter] string $encoded): ?string;
}
