<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\ListingTag\Serializer;

use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslationInterface;
use Aurora\Module\Ecommerce\ListingTag\Serializer\ListingTagSerializer;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ListingTagSerializerTest extends TestCase
{
    private function makeTag(
        int $id = 1,
        string $color = '#ff0000',
        bool $isVisible = true,
        array $translations = [],
        string $createdAt = '2026-01-01T10:00:00+00:00',
        string $updatedAt = '2026-01-02T10:00:00+00:00',
    ): ListingTagInterface {
        $tag = $this->createStub(ListingTagInterface::class);
        $tag->method('getId')->willReturn($id);
        $tag->method('getColor')->willReturn($color);
        $tag->method('isVisible')->willReturn($isVisible);
        $tag->method('getTranslations')->willReturn(new ArrayCollection($translations));
        $tag->method('getCreatedAt')->willReturn(new DateTimeImmutable($createdAt));
        $tag->method('getUpdatedAt')->willReturn(new DateTimeImmutable($updatedAt));

        return $tag;
    }

    private function makeTranslation(string $locale, string $name, string $slug, ?string $description = null): ListingTagTranslationInterface
    {
        $translation = $this->createStub(ListingTagTranslationInterface::class);
        $translation->method('getLocale')->willReturn($locale);
        $translation->method('getName')->willReturn($name);
        $translation->method('getSlug')->willReturn($slug);
        $translation->method('getDescription')->willReturn($description);

        return $translation;
    }

    public function testSerializeReturnsExpectedShape(): void
    {
        $result = (new ListingTagSerializer())->serialize($this->makeTag());

        self::assertSame(1, $result['id']);
        self::assertSame('#ff0000', $result['color']);
        self::assertTrue($result['isVisible']);
        self::assertSame([], $result['translations']);
        self::assertSame('2026-01-01T10:00:00+00:00', $result['createdAt']);
        self::assertSame('2026-01-02T10:00:00+00:00', $result['updatedAt']);
    }

    public function testSerializeIndexesTranslationsByLocale(): void
    {
        $translations = [
            'fr' => $this->makeTranslation('fr', 'Promo', 'promo', 'En promotion'),
            'en' => $this->makeTranslation('en', 'Sale', 'sale'),
        ];

        $result = (new ListingTagSerializer())->serialize($this->makeTag(translations: $translations));

        self::assertArrayHasKey('fr', $result['translations']);
        self::assertArrayHasKey('en', $result['translations']);
        self::assertSame('Promo', $result['translations']['fr']['name']);
        self::assertSame('promo', $result['translations']['fr']['slug']);
        self::assertSame('En promotion', $result['translations']['fr']['description']);
        self::assertSame('Sale', $result['translations']['en']['name']);
        self::assertNull($result['translations']['en']['description']);
    }

    public function testSerializeHiddenTagReportsNotVisible(): void
    {
        $result = (new ListingTagSerializer())->serialize($this->makeTag(isVisible: false));

        self::assertFalse($result['isVisible']);
    }
}
