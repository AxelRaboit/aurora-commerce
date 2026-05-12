<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentFolder\Serializer;

use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Aurora\Module\Ged\DocumentFolder\Serializer\DocumentFolderSerializer;
use PHPUnit\Framework\TestCase;

final class DocumentFolderSerializerTest extends TestCase
{
    private function makeFolder(int $id, string $name, ?int $parentId, int $position): DocumentFolderInterface
    {
        $parent = null;
        if (null !== $parentId) {
            $parent = $this->createStub(DocumentFolderInterface::class);
            $parent->method('getId')->willReturn($parentId);
        }

        $folder = $this->createStub(DocumentFolderInterface::class);
        $folder->method('getId')->willReturn($id);
        $folder->method('getName')->willReturn($name);
        $folder->method('getParent')->willReturn($parent);
        $folder->method('getPosition')->willReturn($position);

        return $folder;
    }

    public function testSerializeWithParentReturnsParentId(): void
    {
        $result = (new DocumentFolderSerializer())->serialize($this->makeFolder(5, 'Contracts', 2, 3));

        self::assertSame(5, $result['id']);
        self::assertSame('Contracts', $result['name']);
        self::assertSame(2, $result['parentId']);
        self::assertSame(3, $result['position']);
    }

    public function testSerializeWithoutParentReturnsNullParentId(): void
    {
        $result = (new DocumentFolderSerializer())->serialize($this->makeFolder(1, 'Root', null, 0));

        self::assertNull($result['parentId']);
        self::assertSame(0, $result['position']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $result = (new DocumentFolderSerializer())->serialize($this->makeFolder(1, 'Root', null, 0));

        self::assertSame(['id', 'name', 'parentId', 'position'], array_keys($result));
    }
}
