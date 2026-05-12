<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentCategory\Serializer;

use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Aurora\Module\Ged\DocumentCategory\Serializer\DocumentCategorySerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DocumentCategorySerializerTest extends TestCase
{
    private function makeCategory(
        int $id = 1,
        string $name = 'Legal',
        string $slug = 'legal',
        ?string $description = 'Legal documents',
        string $createdAt = '2025-01-15T10:00:00+00:00',
        string $updatedAt = '2025-06-01T12:30:00+00:00',
    ): DocumentCategoryInterface {
        $category = $this->createStub(DocumentCategoryInterface::class);
        $category->method('getId')->willReturn($id);
        $category->method('getName')->willReturn($name);
        $category->method('getSlug')->willReturn($slug);
        $category->method('getDescription')->willReturn($description);
        $category->method('getCreatedAt')->willReturn(new DateTimeImmutable($createdAt));
        $category->method('getUpdatedAt')->willReturn(new DateTimeImmutable($updatedAt));

        return $category;
    }

    public function testSerializeReturnsAllExpectedFields(): void
    {
        $result = (new DocumentCategorySerializer())->serialize($this->makeCategory());

        self::assertSame(1, $result['id']);
        self::assertSame('Legal', $result['name']);
        self::assertSame('legal', $result['slug']);
        self::assertSame('Legal documents', $result['description']);
    }

    public function testSerializeDatetimesInAtomFormat(): void
    {
        $result = (new DocumentCategorySerializer())->serialize($this->makeCategory());

        self::assertSame('2025-01-15T10:00:00+00:00', $result['createdAt']);
        self::assertSame('2025-06-01T12:30:00+00:00', $result['updatedAt']);
    }

    public function testSerializeWithNullDescriptionPreservesNull(): void
    {
        $result = (new DocumentCategorySerializer())->serialize($this->makeCategory(description: null));

        self::assertNull($result['description']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $result = (new DocumentCategorySerializer())->serialize($this->makeCategory());

        self::assertSame(['id', 'name', 'slug', 'description', 'createdAt', 'updatedAt'], array_keys($result));
    }
}
