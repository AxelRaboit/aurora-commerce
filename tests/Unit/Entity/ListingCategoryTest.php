<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslation;
use PHPUnit\Framework\TestCase;

final class ListingCategoryTest extends TestCase
{
    public function testRootByDefault(): void
    {
        $category = new ListingCategory();

        self::assertTrue($category->isRoot());
        self::assertNull($category->getParent());
        self::assertSame(0, $category->getDepth());
        self::assertTrue($category->isVisible());
        self::assertSame(0, $category->getPosition());
    }

    public function testParentAndDepth(): void
    {
        $root = new ListingCategory();
        $child = new ListingCategory();
        $grandchild = new ListingCategory();

        $child->setParent($root);
        $grandchild->setParent($child);

        self::assertFalse($child->isRoot());
        self::assertSame(1, $child->getDepth());
        self::assertSame(2, $grandchild->getDepth());
        self::assertSame($root, $child->getParent());
    }

    public function testAddChildLinksParent(): void
    {
        $root = new ListingCategory();
        $child = new ListingCategory();

        $root->addChild($child);

        self::assertCount(1, $root->getChildren());
        self::assertSame($root, $child->getParent());
    }

    public function testRemoveChildDetaches(): void
    {
        $root = new ListingCategory();
        $child = new ListingCategory();

        $root->addChild($child);
        $root->removeChild($child);

        self::assertCount(0, $root->getChildren());
        self::assertNull($child->getParent());
    }

    public function testTranslateCreatesTranslationOnce(): void
    {
        $category = new ListingCategory();

        $translation = $category->translate('en');
        $translation->setName('Books');
        $translation->setSlug('books');

        self::assertSame($category, $translation->getCategory());
        self::assertSame('en', $translation->getLocale());
        self::assertSame($translation, $category->getTranslation('en'));

        $again = $category->translate('en');
        self::assertSame($translation, $again);
        self::assertCount(1, $category->getTranslations());
    }

    public function testAddTranslationKeyedByLocale(): void
    {
        $category = new ListingCategory();
        $translation = new ListingCategoryTranslation();
        $translation->setLocale('fr');
        $translation->setName('Livres');
        $translation->setSlug('livres');

        $category->addTranslation($translation);

        self::assertSame($translation, $category->getTranslation('fr'));
        self::assertSame($category, $translation->getCategory());
    }
}
