<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\VaultUserConfig\Dto;

use Aurora\Module\Vault\VaultUserConfig\Dto\VaultUserConfigInputFactory;
use PHPUnit\Framework\TestCase;

final class VaultUserConfigInputFactoryTest extends TestCase
{
    public function testFromArrayTrimsSalt(): void
    {
        $input = (new VaultUserConfigInputFactory())->fromArray(['argon2Salt' => '  salt  ']);

        self::assertSame('salt', $input->getArgon2Salt());
    }

    public function testFromArrayWithMissingSalt(): void
    {
        $input = (new VaultUserConfigInputFactory())->fromArray([]);

        self::assertSame('', $input->getArgon2Salt());
    }
}
