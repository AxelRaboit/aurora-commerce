<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Dev\MountPoint\Dto;

use Aurora\Core\Dev\MountPoint\Dto\MountPointInput;
use Aurora\Core\Dev\MountPoint\Enum\MountPointTypeEnum;
use PHPUnit\Framework\TestCase;

final class MountPointInputTest extends TestCase
{
    public function testMinimalConstructor(): void
    {
        $input = new MountPointInput('Prod', MountPointTypeEnum::Database, 'db.example.com');

        self::assertSame('Prod', $input->getName());
        self::assertSame(MountPointTypeEnum::Database, $input->getType());
        self::assertSame('db.example.com', $input->getHost());
        self::assertNull($input->getPort());
        self::assertNull($input->getUsername());
        self::assertNull($input->getPassword());
        self::assertNull($input->getDatabase());
        self::assertNull($input->getSshPublicKey());
        self::assertNull($input->getSshPrivateKey());
        self::assertSame([], $input->getConfig());
    }

    public function testFullConstructor(): void
    {
        $input = new MountPointInput(
            name: 'Prod',
            type: MountPointTypeEnum::Sftp,
            host: 'sftp.example.com',
            port: 22,
            username: 'admin',
            password: 'secret',
            database: 'mydb',
            sshPublicKey: 'public-key',
            sshPrivateKey: 'private-key',
            config: ['timeout' => 30],
        );

        self::assertSame(22, $input->getPort());
        self::assertSame('admin', $input->getUsername());
        self::assertSame('secret', $input->getPassword());
        self::assertSame('mydb', $input->getDatabase());
        self::assertSame('public-key', $input->getSshPublicKey());
        self::assertSame('private-key', $input->getSshPrivateKey());
        self::assertSame(['timeout' => 30], $input->getConfig());
    }
}
