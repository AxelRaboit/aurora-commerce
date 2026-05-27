<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Tools\Vault\VaultUserConfig\Serializer;

use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
use Aurora\Module\Tools\Vault\VaultUserConfig\Serializer\VaultUserConfigSerializer;
use PHPUnit\Framework\TestCase;

final class VaultUserConfigSerializerTest extends TestCase
{
    public function testSerializeReturnsArgon2Salt(): void
    {
        $config = $this->createStub(VaultUserConfigInterface::class);
        $config->method('getArgon2Salt')->willReturn('salt-value');

        $result = (new VaultUserConfigSerializer())->serialize($config);

        self::assertSame(['argon2Salt' => 'salt-value'], $result);
    }
}
