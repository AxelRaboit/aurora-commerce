<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Media\Serializer;

use Aurora\Core\Media\Entity\MediaFolderInterface;
use Aurora\Core\Media\Serializer\MediaFolderSerializer;
use PHPUnit\Framework\TestCase;

final class MediaFolderSerializerTest extends TestCase
{
    private function makeFolder(
        int $id = 1,
        string $name = 'Photos',
        ?MediaFolderInterface $parent = null,
        int $position = 0,
    ): MediaFolderInterface {
        $folder = $this->createStub(MediaFolderInterface::class);
        $folder->method('getId')->willReturn($id);
        $folder->method('getName')->willReturn($name);
        $folder->method('getParent')->willReturn($parent);
        $folder->method('getPosition')->willReturn($position);

        return $folder;
    }

    public function testSerializeReturnsAllExpectedFields(): void
    {
        $result = (new MediaFolderSerializer())->serialize($this->makeFolder());

        self::assertSame(1, $result['id']);
        self::assertSame('Photos', $result['name']);
        self::assertNull($result['parentId']);
        self::assertSame(0, $result['position']);
        self::assertSame(0, $result['mediaCount']);
    }

    public function testSerializeIncludesParentId(): void
    {
        $parent = $this->makeFolder(id: 10);
        $result = (new MediaFolderSerializer())->serialize($this->makeFolder(parent: $parent));

        self::assertSame(10, $result['parentId']);
    }

    public function testSerializePositionAndMediaCountDefaults(): void
    {
        $result = (new MediaFolderSerializer())->serialize($this->makeFolder(position: 7));

        self::assertSame(7, $result['position']);
        self::assertSame(0, $result['mediaCount']);
    }

    public function testWithMediaCountsReturnsClonedSerializerWithCounts(): void
    {
        $serializer = new MediaFolderSerializer();
        $cloned = $serializer->withMediaCounts([1 => 5, 2 => 12]);

        self::assertNotSame($serializer, $cloned);

        $result = $cloned->serialize($this->makeFolder(id: 1));
        self::assertSame(5, $result['mediaCount']);

        $result2 = $cloned->serialize($this->makeFolder(id: 2));
        self::assertSame(12, $result2['mediaCount']);
    }

    public function testWithMediaCountsDefaultsToZeroForUnknownFolder(): void
    {
        $serializer = (new MediaFolderSerializer())->withMediaCounts([1 => 5]);
        $result = $serializer->serialize($this->makeFolder(id: 999));

        self::assertSame(0, $result['mediaCount']);
    }

    public function testWithMediaCountsDoesNotMutateOriginal(): void
    {
        $serializer = new MediaFolderSerializer();
        $serializer->withMediaCounts([1 => 5]);

        $result = $serializer->serialize($this->makeFolder(id: 1));
        self::assertSame(0, $result['mediaCount'], 'original serializer should still report 0');
    }
}
