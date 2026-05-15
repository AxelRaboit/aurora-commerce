<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\VaultFolder\Serializer;

use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use Aurora\Module\Vault\VaultFolder\Serializer\VaultFolderSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VaultFolderSerializerTest extends TestCase
{
    private function makeFolder(?VaultFolderInterface $parent = null, ?string $color = '#ff0000'): VaultFolderInterface
    {
        $folder = $this->createStub(VaultFolderInterface::class);
        $folder->method('getId')->willReturn(1);
        $folder->method('getName')->willReturn('Passwords');
        $folder->method('getColor')->willReturn($color);
        $folder->method('getPosition')->willReturn(3);
        $folder->method('getParent')->willReturn($parent);
        $folder->method('getCreatedAt')->willReturn(new DateTimeImmutable('2026-01-01T10:00:00+00:00'));
        $folder->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2026-01-02T10:00:00+00:00'));

        return $folder;
    }

    public function testSerializeReturnsExpectedShape(): void
    {
        $result = (new VaultFolderSerializer())->serialize($this->makeFolder());

        self::assertSame(1, $result['id']);
        self::assertSame('Passwords', $result['name']);
        self::assertSame('#ff0000', $result['color']);
        self::assertSame(3, $result['position']);
        self::assertNull($result['parentId']);
    }

    public function testSerializeIncludesParentId(): void
    {
        $parent = $this->makeFolder();
        $result = (new VaultFolderSerializer())->serialize($this->makeFolder(parent: $parent));

        self::assertSame(1, $result['parentId']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $result = (new VaultFolderSerializer())->serialize($this->makeFolder());

        self::assertSame(
            ['id', 'name', 'color', 'position', 'parentId', 'createdAt', 'updatedAt'],
            array_keys($result),
        );
    }
}
