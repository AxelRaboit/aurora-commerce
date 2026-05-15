<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\VaultUserConfig\Dto;

use Aurora\Module\Vault\VaultUserConfig\Dto\VaultUserConfigInput;
use PHPUnit\Framework\TestCase;

final class VaultUserConfigInputTest extends TestCase
{
    public function testDefaultSalt(): void
    {
        self::assertSame('', (new VaultUserConfigInput())->getArgon2Salt());
    }

    public function testGetArgon2SaltReturnsConstructorValue(): void
    {
        $salt = 'a-very-long-salt-value-min-16';
        self::assertSame($salt, (new VaultUserConfigInput($salt))->getArgon2Salt());
    }
}
