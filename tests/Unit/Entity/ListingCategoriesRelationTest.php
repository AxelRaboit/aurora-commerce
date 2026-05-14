<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use PHPUnit\Framework\TestCase;

final class ListingCategoriesRelationTest extends TestCase
{
    public function testListingStartsWithoutCategories(): void
    {
        $listing = new Listing();

        self::assertCount(0, $listing->getCategories());
    }

    public function testAddCategoriesAttachesThem(): void
    {
        $listing = new Listing();
        $books = new ListingCategory();
        $music = new ListingCategory();

        $listing->addCategory($books);
        $listing->addCategory($music);

        self::assertCount(2, $listing->getCategories());
        self::assertTrue($listing->getCategories()->contains($books));
        self::assertTrue($listing->getCategories()->contains($music));
    }

    public function testAddCategoryIsIdempotent(): void
    {
        $listing = new Listing();
        $category = new ListingCategory();

        $listing->addCategory($category);
        $listing->addCategory($category);

        self::assertCount(1, $listing->getCategories());
    }

    public function testRemoveCategoryDetachesIt(): void
    {
        $listing = new Listing();
        $category = new ListingCategory();

        $listing->addCategory($category);
        $listing->removeCategory($category);

        self::assertCount(0, $listing->getCategories());
    }

    public function testClearCategoriesRemovesAll(): void
    {
        $listing = new Listing();
        $listing->addCategory(new ListingCategory());
        $listing->addCategory(new ListingCategory());

        $listing->clearCategories();

        self::assertCount(0, $listing->getCategories());
    }

    public function testInverseSideStartsEmpty(): void
    {
        $category = new ListingCategory();

        self::assertCount(0, $category->getListings());
    }
}
