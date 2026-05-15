<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\MountPoint\Dto;

use Aurora\Core\MountPoint\Dto\MountPointInputFactory;
use Aurora\Core\MountPoint\Enum\MountPointTypeEnum;
use PHPUnit\Framework\TestCase;

final class MountPointInputFactoryTest extends TestCase
{
    public function testFromArrayWithAllFields(): void
    {
        $input = (new MountPointInputFactory())->fromArray([
            'name' => '  Prod  ',
            'type' => 'database',
            'host' => '  db.example.com  ',
            'port' => '5432',
            'username' => '  admin  ',
            'password' => '  secret  ',
            'database' => '  mydb  ',
            'sshPublicKey' => 'public',
            'sshPrivateKey' => 'private',
            'config' => ['key' => 'value'],
        ]);

        self::assertSame('Prod', $input->getName());
        self::assertSame(MountPointTypeEnum::Database, $input->getType());
        self::assertSame('db.example.com', $input->getHost());
        self::assertSame(5432, $input->getPort());
        self::assertSame('admin', $input->getUsername());
        self::assertSame('secret', $input->getPassword());
        self::assertSame(['key' => 'value'], $input->getConfig());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new MountPointInputFactory())->fromArray(['name' => 'X', 'host' => 'h']);

        self::assertSame(MountPointTypeEnum::Database, $input->getType());
        self::assertNull($input->getPort());
        self::assertNull($input->getUsername());
        self::assertSame([], $input->getConfig());
    }
}
