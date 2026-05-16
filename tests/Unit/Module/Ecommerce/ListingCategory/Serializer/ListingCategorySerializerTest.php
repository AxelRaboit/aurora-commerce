<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\ListingCategory\Serializer;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslationInterface;
use Aurora\Module\Ecommerce\ListingCategory\Serializer\ListingCategorySerializer;
use Aurora\Tests\Concern\CreatesStorageUrlGenerators;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ListingCategorySerializerTest extends TestCase
{
    use CreatesStorageUrlGenerators;

    private function makeCategory(
        int $id = 1,
        ?ListingCategoryInterface $parent = null,
        int $position = 0,
        bool $isVisible = true,
        int $depth = 0,
        bool $hasChildren = false,
        ?MediaInterface $image = null,
        array $translations = [],
        string $createdAt = '2026-01-01T10:00:00+00:00',
        string $updatedAt = '2026-01-02T10:00:00+00:00',
    ): ListingCategoryInterface {
        $category = $this->createStub(ListingCategoryInterface::class);
        $category->method('getId')->willReturn($id);
        $category->method('getParent')->willReturn($parent);
        $category->method('getPosition')->willReturn($position);
        $category->method('isVisible')->willReturn($isVisible);
        $category->method('getDepth')->willReturn($depth);

        $childrenCollection = new ArrayCollection($hasChildren ? [$this->createStub(ListingCategoryInterface::class)] : []);
        $category->method('getChildren')->willReturn($childrenCollection);

        $category->method('getImage')->willReturn($image);
        $category->method('getTranslations')->willReturn(new ArrayCollection($translations));
        $category->method('getCreatedAt')->willReturn(new DateTimeImmutable($createdAt));
        $category->method('getUpdatedAt')->willReturn(new DateTimeImmutable($updatedAt));

        return $category;
    }

    private function makeTranslation(string $locale, string $name, string $slug): ListingCategoryTranslationInterface
    {
        $translation = $this->createStub(ListingCategoryTranslationInterface::class);
        $translation->method('getLocale')->willReturn($locale);
        $translation->method('getName')->willReturn($name);
        $translation->method('getSlug')->willReturn($slug);
        $translation->method('getDescription')->willReturn(null);
        $translation->method('getSeoTitle')->willReturn(null);
        $translation->method('getSeoDescription')->willReturn(null);

        return $translation;
    }

    public function testSerializeReturnsExpectedShape(): void
    {
        $result = (new ListingCategorySerializer($this->makeMediaUrlGenerator()))->serialize($this->makeCategory());

        self::assertSame(1, $result['id']);
        self::assertNull($result['parentId']);
        self::assertSame(0, $result['position']);
        self::assertTrue($result['isVisible']);
        self::assertSame(0, $result['depth']);
        self::assertFalse($result['hasChildren']);
        self::assertNull($result['image']);
        self::assertSame([], $result['translations']);
        self::assertSame('2026-01-01T10:00:00+00:00', $result['createdAt']);
        self::assertSame('2026-01-02T10:00:00+00:00', $result['updatedAt']);
    }

    public function testSerializeWithParentReturnsParentId(): void
    {
        $parent = $this->makeCategory(id: 5);
        $result = (new ListingCategorySerializer($this->makeMediaUrlGenerator()))->serialize($this->makeCategory(parent: $parent));

        self::assertSame(5, $result['parentId']);
    }

    public function testSerializeFlagsHasChildren(): void
    {
        $result = (new ListingCategorySerializer($this->makeMediaUrlGenerator()))->serialize($this->makeCategory(hasChildren: true));

        self::assertTrue($result['hasChildren']);
    }

    public function testSerializeIncludesImageWhenPresent(): void
    {
        $image = $this->createStub(MediaInterface::class);
        $image->method('getId')->willReturn(99);
        $image->method('getPath')->willReturn('photo.jpg');
        $image->method('getAlt')->willReturn('Cover photo');

        $result = (new ListingCategorySerializer($this->makeMediaUrlGenerator()))->serialize($this->makeCategory(image: $image));

        self::assertSame(99, $result['image']['id']);
        self::assertSame('/uploads/photo.jpg', $result['image']['url']);
        self::assertSame('Cover photo', $result['image']['alt']);
    }

    public function testSerializeIndexesTranslationsByLocale(): void
    {
        $translations = [
            'fr' => $this->makeTranslation('fr', 'Bijoux', 'bijoux'),
            'en' => $this->makeTranslation('en', 'Jewelry', 'jewelry'),
        ];

        $result = (new ListingCategorySerializer($this->makeMediaUrlGenerator()))->serialize($this->makeCategory(translations: $translations));

        self::assertArrayHasKey('fr', $result['translations']);
        self::assertArrayHasKey('en', $result['translations']);
        self::assertSame('Bijoux', $result['translations']['fr']['name']);
        self::assertSame('Jewelry', $result['translations']['en']['name']);
    }
}
