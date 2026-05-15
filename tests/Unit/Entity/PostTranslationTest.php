<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use PHPUnit\Framework\TestCase;

final class PostTranslationTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new PostTranslation())->getId());
    }

    public function testDefaultValues(): void
    {
        $translation = new PostTranslation();

        self::assertNull($translation->getTitle());
        self::assertNull($translation->getSlug());
        self::assertSame([], $translation->getBlocks());
        self::assertNull($translation->getMetaTitle());
        self::assertNull($translation->getMetaDescription());
        self::assertSame([], $translation->getCustomFields());
        self::assertNull($translation->getOgImage());
        self::assertNull($translation->getCanonicalUrl());
        self::assertFalse($translation->isNoindex());
        self::assertNull($translation->getFocusKeyword());
        self::assertNull($translation->getJsonLd());
        self::assertNull($translation->getSearchContent());
    }

    public function testLocaleAndPostGettersAndSetters(): void
    {
        $post = new Post();
        $translation = (new PostTranslation())->setLocale('fr')->setPost($post);

        self::assertSame('fr', $translation->getLocale());
        self::assertSame($post, $translation->getPost());
    }

    public function testTitleAndSlug(): void
    {
        $translation = (new PostTranslation())->setTitle('Hello')->setSlug('hello');

        self::assertSame('Hello', $translation->getTitle());
        self::assertSame('hello', $translation->getSlug());
    }

    public function testBlocksAndCustomFields(): void
    {
        $blocks = [['type' => 'paragraph', 'content' => 'hello']];
        $customFields = ['key' => 'value'];

        $translation = (new PostTranslation())->setBlocks($blocks)->setCustomFields($customFields);

        self::assertSame($blocks, $translation->getBlocks());
        self::assertSame($customFields, $translation->getCustomFields());
    }

    public function testMetaFields(): void
    {
        $translation = (new PostTranslation())
            ->setMetaTitle('Meta Title')
            ->setMetaDescription('Meta Description');

        self::assertSame('Meta Title', $translation->getMetaTitle());
        self::assertSame('Meta Description', $translation->getMetaDescription());
    }

    public function testOgImageGetterAndSetter(): void
    {
        $image = $this->createStub(MediaInterface::class);
        $translation = (new PostTranslation())->setOgImage($image);

        self::assertSame($image, $translation->getOgImage());

        $translation->setOgImage(null);
        self::assertNull($translation->getOgImage());
    }

    public function testCanonicalUrlAndNoindex(): void
    {
        $translation = (new PostTranslation())
            ->setCanonicalUrl('https://example.com')
            ->setNoindex(true);

        self::assertSame('https://example.com', $translation->getCanonicalUrl());
        self::assertTrue($translation->isNoindex());
    }

    public function testFocusKeywordAndJsonLd(): void
    {
        $jsonLd = ['@type' => 'Article'];
        $translation = (new PostTranslation())
            ->setFocusKeyword('aurora')
            ->setJsonLd($jsonLd);

        self::assertSame('aurora', $translation->getFocusKeyword());
        self::assertSame($jsonLd, $translation->getJsonLd());
    }

    public function testSearchContentGetterAndSetter(): void
    {
        $translation = (new PostTranslation())->setSearchContent('searchable text');

        self::assertSame('searchable text', $translation->getSearchContent());
    }
}
