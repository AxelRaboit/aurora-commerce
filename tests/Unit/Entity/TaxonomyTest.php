<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use PHPUnit\Framework\TestCase;

final class TaxonomyTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Taxonomy())->getId());
    }

    public function testDefaultValues(): void
    {
        $taxonomy = new Taxonomy();

        self::assertFalse($taxonomy->isHierarchical());
        self::assertFalse($taxonomy->isBuiltIn());
    }

    public function testCollectionsInitialized(): void
    {
        $taxonomy = new Taxonomy();

        self::assertCount(0, $taxonomy->getTranslations());
        self::assertCount(0, $taxonomy->getTerms());
        self::assertCount(0, $taxonomy->getPostTypes());
    }

    public function testSlugGetterAndSetter(): void
    {
        $taxonomy = (new Taxonomy())->setSlug('category');

        self::assertSame('category', $taxonomy->getSlug());
    }

    public function testHierarchicalGetterAndSetter(): void
    {
        $taxonomy = (new Taxonomy())->setHierarchical(true);

        self::assertTrue($taxonomy->isHierarchical());
    }

    public function testIsBuiltInGetterAndSetter(): void
    {
        $taxonomy = (new Taxonomy())->setIsBuiltIn(true);

        self::assertTrue($taxonomy->isBuiltIn());
    }

    public function testTranslateCreatesAndCachesTranslation(): void
    {
        $taxonomy = new Taxonomy();

        $fr = $taxonomy->translate('fr');

        self::assertSame('fr', $fr->getLocale());
        self::assertSame($taxonomy, $fr->getTaxonomy());
        self::assertSame($fr, $taxonomy->translate('fr'), 'same locale returns cached');
        self::assertNotSame($fr, $taxonomy->translate('en'), 'different locale creates new');
    }

    public function testGetTranslationReturnsNullForMissingLocale(): void
    {
        self::assertNull((new Taxonomy())->getTranslation('de'));
    }

    public function testFindTermByIdReturnsNullForEmptyTaxonomy(): void
    {
        self::assertNull((new Taxonomy())->findTermById(42));
    }
}
