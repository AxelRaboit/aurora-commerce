<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Dto;

use Aurora\Module\Editorial\Post\Dto\PostTranslationInput;
use PHPUnit\Framework\TestCase;

final class PostTranslationInputTest extends TestCase
{
    public function testFromArrayWithMinimalData(): void
    {
        $input = PostTranslationInput::fromArray([]);

        self::assertNull($input->title);
        self::assertNull($input->slug);
        self::assertSame([], $input->blocks);
        self::assertNull($input->metaTitle);
        self::assertNull($input->metaDescription);
        self::assertSame([], $input->customFields);
        self::assertNull($input->ogImageMediaId);
        self::assertNull($input->canonicalUrl);
        self::assertFalse($input->noindex);
        self::assertNull($input->focusKeyword);
        self::assertNull($input->jsonLd);
    }

    public function testFromArrayTrimsStringFields(): void
    {
        $input = PostTranslationInput::fromArray([
            'title' => '  Hello  ',
            'slug' => '  hello  ',
            'metaTitle' => '  Meta  ',
            'metaDescription' => '  Description  ',
            'canonicalUrl' => '  https://example.com  ',
            'focusKeyword' => '  keyword  ',
        ]);

        self::assertSame('Hello', $input->title);
        self::assertSame('hello', $input->slug);
        self::assertSame('Meta', $input->metaTitle);
        self::assertSame('Description', $input->metaDescription);
        self::assertSame('https://example.com', $input->canonicalUrl);
        self::assertSame('keyword', $input->focusKeyword);
    }

    public function testFromArrayBlocksAndCustomFieldsNonArrayBecomesEmpty(): void
    {
        $input = PostTranslationInput::fromArray([
            'blocks' => 'not-an-array',
            'customFields' => 42,
        ]);

        self::assertSame([], $input->blocks);
        self::assertSame([], $input->customFields);
    }

    public function testFromArrayBlocksAndCustomFieldsAccepted(): void
    {
        $input = PostTranslationInput::fromArray([
            'blocks' => [['type' => 'paragraph', 'content' => 'hi']],
            'customFields' => ['key' => 'value'],
        ]);

        self::assertCount(1, $input->blocks);
        self::assertSame(['key' => 'value'], $input->customFields);
    }

    public function testOgImageMediaIdAcceptsPositiveOnly(): void
    {
        $valid = PostTranslationInput::fromArray(['ogImageMediaId' => 7]);
        self::assertSame(7, $valid->ogImageMediaId);

        $zero = PostTranslationInput::fromArray(['ogImageMediaId' => 0]);
        self::assertNull($zero->ogImageMediaId);

        $negative = PostTranslationInput::fromArray(['ogImageMediaId' => -1]);
        self::assertNull($negative->ogImageMediaId);
    }

    public function testNoindexFlag(): void
    {
        self::assertTrue(PostTranslationInput::fromArray(['noindex' => true])->noindex);
        self::assertFalse(PostTranslationInput::fromArray(['noindex' => false])->noindex);
        self::assertFalse(PostTranslationInput::fromArray([])->noindex);
    }

    public function testJsonLdAcceptsArrayDirectly(): void
    {
        $input = PostTranslationInput::fromArray([
            'jsonLd' => ['@context' => 'https://schema.org', '@type' => 'Article'],
        ]);

        self::assertSame(['@context' => 'https://schema.org', '@type' => 'Article'], $input->jsonLd);
    }

    public function testJsonLdParsesJsonString(): void
    {
        $input = PostTranslationInput::fromArray([
            'jsonLd' => '{"@type":"Article","name":"X"}',
        ]);

        self::assertSame(['@type' => 'Article', 'name' => 'X'], $input->jsonLd);
    }

    public function testJsonLdIgnoresInvalidJsonString(): void
    {
        $input = PostTranslationInput::fromArray([
            'jsonLd' => 'not-json',
        ]);

        self::assertNull($input->jsonLd);
    }

    public function testJsonLdIgnoresEmptyString(): void
    {
        $input = PostTranslationInput::fromArray([
            'jsonLd' => '   ',
        ]);

        self::assertNull($input->jsonLd);
    }
}
