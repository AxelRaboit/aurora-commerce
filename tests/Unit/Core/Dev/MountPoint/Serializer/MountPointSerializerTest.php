<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Dev\MountPoint\Serializer;

use Aurora\Core\Dev\MountPoint\Entity\MountPointInterface;
use Aurora\Core\Dev\MountPoint\Enum\MountPointTypeEnum;
use Aurora\Core\Dev\MountPoint\Serializer\MountPointSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MountPointSerializerTest extends TestCase
{
    private function makeMountPoint(
        ?string $password = 'secret',
        ?string $sshPrivateKey = null,
        ?DateTimeImmutable $lastTestedAt = null,
        ?bool $lastTestSuccessful = null,
    ): MountPointInterface {
        $mp = $this->createStub(MountPointInterface::class);
        $mp->method('getId')->willReturn(1);
        $mp->method('getName')->willReturn('Prod DB');
        $mp->method('getType')->willReturn(MountPointTypeEnum::Database);
        $mp->method('getHost')->willReturn('db.example.com');
        $mp->method('getPort')->willReturn(5432);
        $mp->method('getUsername')->willReturn('admin');
        $mp->method('getPassword')->willReturn($password);
        $mp->method('getSshPrivateKey')->willReturn($sshPrivateKey);
        $mp->method('getSshPublicKey')->willReturn('ssh-rsa AAAA...');
        $mp->method('getDatabase')->willReturn('mydb');
        $mp->method('getConfig')->willReturn([]);
        $mp->method('getLastTestedAt')->willReturn($lastTestedAt);
        $mp->method('isLastTestSuccessful')->willReturn($lastTestSuccessful);
        $mp->method('getCreatedAt')->willReturn(new DateTimeImmutable('2026-01-01T10:00:00+00:00'));
        $mp->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2026-01-02T10:00:00+00:00'));

        return $mp;
    }

    public function testSerializeReturnsExpectedShape(): void
    {
        $result = (new MountPointSerializer())->serialize($this->makeMountPoint());

        self::assertSame(1, $result['id']);
        self::assertSame('Prod DB', $result['name']);
        self::assertSame('database', $result['type']);
        self::assertSame('db.example.com', $result['host']);
        self::assertSame(5432, $result['port']);
        self::assertSame('admin', $result['username']);
        self::assertTrue($result['hasPassword']);
        self::assertFalse($result['hasSshPrivateKey']);
        self::assertSame('mydb', $result['database']);
        self::assertSame('2026-01-01T10:00:00+00:00', $result['createdAt']);
    }

    public function testHasPasswordReturnsFalseWhenNoPassword(): void
    {
        $result = (new MountPointSerializer())->serialize($this->makeMountPoint(password: null));

        self::assertFalse($result['hasPassword']);
    }

    public function testHasSshPrivateKeyReturnsTrueWhenSet(): void
    {
        $result = (new MountPointSerializer())->serialize($this->makeMountPoint(sshPrivateKey: 'private-key'));

        self::assertTrue($result['hasSshPrivateKey']);
    }

    public function testLastTestedAtReturnsNullWhenAbsent(): void
    {
        $result = (new MountPointSerializer())->serialize($this->makeMountPoint());

        self::assertNull($result['lastTestedAt']);
    }

    public function testLastTestedAtFormattedAsAtom(): void
    {
        $date = new DateTimeImmutable('2026-02-01T10:00:00+00:00');
        $result = (new MountPointSerializer())->serialize($this->makeMountPoint(lastTestedAt: $date, lastTestSuccessful: true));

        self::assertSame('2026-02-01T10:00:00+00:00', $result['lastTestedAt']);
        self::assertTrue($result['lastTestSuccessful']);
    }
}
