<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use PHPUnit\Framework\TestCase;

final class TaxonomyTermTest extends TestCase
{
    public function testGetAncestorsReturnsRootFirstChain(): void
    {
        $taxonomy = new Taxonomy();

        $root = (new TaxonomyTerm())->setTaxonomy($taxonomy);
        $middle = (new TaxonomyTerm())->setTaxonomy($taxonomy)->setParent($root);
        $leaf = (new TaxonomyTerm())->setTaxonomy($taxonomy)->setParent($middle);

        self::assertSame([], $root->getAncestors());
        self::assertSame([$root], $middle->getAncestors());
        self::assertSame([$root, $middle], $leaf->getAncestors());
    }

    public function testTranslateCreatesAndCachesTranslation(): void
    {
        $term = (new TaxonomyTerm())->setTaxonomy(new Taxonomy());

        $first = $term->translate('fr');

        self::assertSame('fr', $first->getLocale());
        self::assertSame($term, $first->getTerm());
        self::assertSame($first, $term->translate('fr'), 'same locale returns cached instance');
        self::assertNotSame($first, $term->translate('en'), 'different locale creates new instance');
    }

    public function testIsDescendantOfDetectsAncestor(): void
    {
        $taxonomy = new Taxonomy();
        $root = (new TaxonomyTerm())->setTaxonomy($taxonomy);
        $middle = (new TaxonomyTerm())->setTaxonomy($taxonomy)->setParent($root);
        $leaf = (new TaxonomyTerm())->setTaxonomy($taxonomy)->setParent($middle);
        $sibling = (new TaxonomyTerm())->setTaxonomy($taxonomy);

        self::assertTrue($leaf->isDescendantOf($root));
        self::assertTrue($leaf->isDescendantOf($middle));
        self::assertFalse($leaf->isDescendantOf($sibling));
        self::assertFalse($root->isDescendantOf($leaf));
    }
}
