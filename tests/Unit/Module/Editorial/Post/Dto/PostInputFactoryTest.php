<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Dto;

use Aurora\Module\Editorial\Post\Dto\PostInputFactory;
use Aurora\Module\Editorial\Post\Dto\PostTranslationInput;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use PHPUnit\Framework\TestCase;

final class PostInputFactoryTest extends TestCase
{
    public function testFromArrayDefaultsStatusToDraftWhenMissing(): void
    {
        $input = (new PostInputFactory())->fromArray(['postTypeId' => 1]);

        self::assertSame(PostStatusEnum::Draft->value, $input->status);
    }

    public function testFromArrayCoercesPostTypeIdFromString(): void
    {
        $input = (new PostInputFactory())->fromArray(['postTypeId' => '42']);

        self::assertSame(42, $input->postTypeId);
    }

    public function testFromArrayFiltersNonPositiveTermIds(): void
    {
        $input = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'termIds' => [1, '2', '', 0, -1, 3],
        ]);

        self::assertSame([1, 2, 3], $input->termIds);
    }

    public function testFromArrayFiltersNonPositiveRelatedPostIds(): void
    {
        $input = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'relatedPostIds' => [5, '6', '', 0, -2, 7],
        ]);

        self::assertSame([5, 6, 7], $input->relatedPostIds);
    }

    public function testFromArrayWithNonArrayTermIdsReturnsEmptyArray(): void
    {
        $input = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'termIds' => 'not-an-array',
        ]);

        self::assertSame([], $input->termIds);
    }

    public function testFromArrayBuildsTranslationsKeyedByLocale(): void
    {
        $input = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'translations' => [
                'fr' => ['title' => 'Bonjour', 'slug' => 'bonjour'],
                'en' => ['title' => 'Hello',   'slug' => 'hello'],
            ],
        ]);

        self::assertCount(2, $input->translations);
        self::assertArrayHasKey('fr', $input->translations);
        self::assertArrayHasKey('en', $input->translations);
        self::assertInstanceOf(PostTranslationInput::class, $input->translations['fr']);
        self::assertSame('Bonjour', $input->translations['fr']->title);
    }

    public function testFromArrayIgnoresNonArrayTranslationEntries(): void
    {
        $input = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'translations' => [
                'fr' => ['title' => 'Bonjour'],
                'en' => 'not-an-array',
            ],
        ]);

        self::assertCount(1, $input->translations);
        self::assertArrayHasKey('fr', $input->translations);
    }

    public function testFromArrayHandlesFeaturedMediaIdAndScheduling(): void
    {
        $input = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'featuredMediaId' => 7,
            'scheduledAt' => '2026-01-01T10:00:00+00:00',
            'version' => '3',
        ]);

        self::assertSame(7, $input->featuredMediaId);
        self::assertSame('2026-01-01T10:00:00+00:00', $input->scheduledAt);
        self::assertSame(3, $input->version);
    }

    public function testFromArrayNullsFeaturedMediaIdWhenZeroOrMissing(): void
    {
        $input = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'featuredMediaId' => 0,
        ]);

        self::assertNull($input->featuredMediaId);
    }

    public function testFromArrayDefaultsForceFalseAndCommentsEnabledTrue(): void
    {
        $input = (new PostInputFactory())->fromArray(['postTypeId' => 1]);

        self::assertFalse($input->force);
        self::assertTrue($input->commentsEnabled);
    }

    public function testFromArrayRespectsForceAndCommentsEnabledFlags(): void
    {
        $input = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'force' => true,
            'commentsEnabled' => false,
        ]);

        self::assertTrue($input->force);
        self::assertFalse($input->commentsEnabled);
    }

    public function testWithStatusReturnsNewInstanceWithSameOtherFields(): void
    {
        $original = (new PostInputFactory())->fromArray([
            'postTypeId' => 1,
            'status' => PostStatusEnum::Draft->value,
            'featuredMediaId' => 9,
            'termIds' => [1, 2],
        ]);

        $updated = $original->withStatus(PostStatusEnum::Published->value);

        self::assertNotSame($original, $updated);
        self::assertSame(PostStatusEnum::Published->value, $updated->status);
        self::assertSame($original->postTypeId, $updated->postTypeId);
        self::assertSame($original->featuredMediaId, $updated->featuredMediaId);
        self::assertSame($original->termIds, $updated->termIds);
    }
}
