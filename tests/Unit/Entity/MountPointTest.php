<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Dev\MountPoint\Entity\MountPoint;
use Aurora\Module\Dev\MountPoint\Enum\MountPointTypeEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MountPointTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new MountPoint())->getId());
    }

    public function testDefaultValues(): void
    {
        $mp = new MountPoint();

        self::assertNull($mp->getPort());
        self::assertNull($mp->getUsername());
        self::assertNull($mp->getPassword());
        self::assertNull($mp->getDatabase());
        self::assertNull($mp->getSshPublicKey());
        self::assertNull($mp->getSshPrivateKey());
        self::assertSame([], $mp->getConfig());
        self::assertNull($mp->getLastTestedAt());
        self::assertNull($mp->isLastTestSuccessful());
    }

    public function testNameAndTypeAndHost(): void
    {
        $mp = (new MountPoint())
            ->setName('Production DB')
            ->setType(MountPointTypeEnum::Database)
            ->setHost('db.example.com');

        self::assertSame('Production DB', $mp->getName());
        self::assertSame(MountPointTypeEnum::Database, $mp->getType());
        self::assertSame('db.example.com', $mp->getHost());
    }

    public function testCredentialsGettersAndSetters(): void
    {
        $mp = (new MountPoint())
            ->setPort(5432)
            ->setUsername('admin')
            ->setPassword('secret')
            ->setDatabase('mydb');

        self::assertSame(5432, $mp->getPort());
        self::assertSame('admin', $mp->getUsername());
        self::assertSame('secret', $mp->getPassword());
        self::assertSame('mydb', $mp->getDatabase());
    }

    public function testSshKeysGettersAndSetters(): void
    {
        $mp = (new MountPoint())
            ->setSshPublicKey('ssh-rsa AAAA...')
            ->setSshPrivateKey('-----BEGIN PRIVATE KEY-----');

        self::assertSame('ssh-rsa AAAA...', $mp->getSshPublicKey());
        self::assertSame('-----BEGIN PRIVATE KEY-----', $mp->getSshPrivateKey());
    }

    public function testConfigGetterAndSetter(): void
    {
        $config = ['timeout' => 30, 'ssl' => true];
        $mp = (new MountPoint())->setConfig($config);

        self::assertSame($config, $mp->getConfig());
    }

    public function testLastTestedAtAndSuccessful(): void
    {
        $date = new DateTimeImmutable('2026-01-15');
        $mp = (new MountPoint())->setLastTestedAt($date)->setLastTestSuccessful(true);

        self::assertSame($date, $mp->getLastTestedAt());
        self::assertTrue($mp->isLastTestSuccessful());

        $mp->setLastTestSuccessful(false);
        self::assertFalse($mp->isLastTestSuccessful());
    }
}
