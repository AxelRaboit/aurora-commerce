<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentTag\Serializer;

use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Aurora\Module\Ged\DocumentTag\Serializer\DocumentTagSerializer;
use PHPUnit\Framework\TestCase;

final class DocumentTagSerializerTest extends TestCase
{
    private function makeTag(int $id, string $name, ?string $color): DocumentTagInterface
    {
        $tag = $this->createStub(DocumentTagInterface::class);
        $tag->method('getId')->willReturn($id);
        $tag->method('getName')->willReturn($name);
        $tag->method('getColor')->willReturn($color);

        return $tag;
    }

    public function testSerializeReturnsExpectedShape(): void
    {
        $result = (new DocumentTagSerializer())->serialize($this->makeTag(1, 'Urgent', '#ff0000'));

        self::assertSame(1, $result['id']);
        self::assertSame('Urgent', $result['name']);
        self::assertSame('#ff0000', $result['color']);
    }

    public function testSerializeWithNullColorPreservesNull(): void
    {
        $result = (new DocumentTagSerializer())->serialize($this->makeTag(2, 'No color', null));

        self::assertNull($result['color']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $result = (new DocumentTagSerializer())->serialize($this->makeTag(1, 'Tag', '#abc'));

        self::assertSame(['id', 'name', 'color'], array_keys($result));
    }
}
