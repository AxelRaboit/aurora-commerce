<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfig;
use PHPUnit\Framework\TestCase;

final class VaultUserConfigTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new VaultUserConfig())->getId());
    }

    public function testUserGetterAndSetter(): void
    {
        $user = new User();
        $config = (new VaultUserConfig())->setUser($user);

        self::assertSame($user, $config->getUser());
    }

    public function testArgon2SaltGetterAndSetter(): void
    {
        $config = (new VaultUserConfig())->setArgon2Salt('salt-value');

        self::assertSame('salt-value', $config->getArgon2Salt());
    }
}
