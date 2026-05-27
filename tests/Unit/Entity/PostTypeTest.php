<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use PHPUnit\Framework\TestCase;

final class PostTypeTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new PostType())->getId());
    }

    public function testDefaultValues(): void
    {
        $postType = new PostType();

        self::assertNull($postType->getIcon());
        self::assertFalse($postType->hasArchive());
        self::assertFalse($postType->isBuiltIn());
        self::assertSame(['blocks', 'thumbnail', 'excerpt'], $postType->getSupports());
    }

    public function testCollectionsInitialized(): void
    {
        $postType = new PostType();

        self::assertCount(0, $postType->getFields());
        self::assertCount(0, $postType->getPosts());
        self::assertCount(0, $postType->getTaxonomies());
    }

    public function testSlugGetterAndSetter(): void
    {
        $postType = (new PostType())->setSlug('article');

        self::assertSame('article', $postType->getSlug());
    }

    public function testLabelGetterAndSetter(): void
    {
        $postType = (new PostType())->setLabel('Article');

        self::assertSame('Article', $postType->getLabel());
    }

    public function testSupportsReturnsTrueForRegisteredFeature(): void
    {
        $postType = (new PostType())->setSupports(['comments', 'revisions']);

        self::assertTrue($postType->supports('comments'));
        self::assertTrue($postType->supports('revisions'));
        self::assertFalse($postType->supports('gallery'));
    }

    public function testSupportsReturnsFalseWhenEmpty(): void
    {
        self::assertFalse((new PostType())->supports('comments'));
    }

    public function testAddTaxonomyAndRemoveTaxonomy(): void
    {
        $postType = new PostType();
        $taxonomy = $this->createStub(TaxonomyInterface::class);

        $postType->addTaxonomy($taxonomy);
        self::assertCount(1, $postType->getTaxonomies());

        $postType->addTaxonomy($taxonomy);
        self::assertCount(1, $postType->getTaxonomies(), 'duplicate is ignored');

        $postType->removeTaxonomy($taxonomy);
        self::assertCount(0, $postType->getTaxonomies());
    }
}
