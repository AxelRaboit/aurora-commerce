<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use PHPUnit\Framework\TestCase;

final class ListingTagsRelationTest extends TestCase
{
    public function testListingStartsWithoutTags(): void
    {
        $listing = new Listing();

        self::assertCount(0, $listing->getTags());
    }

    public function testAddTagsAttachesThem(): void
    {
        $listing = new Listing();
        $sale = new ListingTag();
        $featured = new ListingTag();

        $listing->addTag($sale);
        $listing->addTag($featured);

        self::assertCount(2, $listing->getTags());
        self::assertTrue($listing->getTags()->contains($sale));
        self::assertTrue($listing->getTags()->contains($featured));
    }

    public function testAddTagIsIdempotent(): void
    {
        $listing = new Listing();
        $tag = new ListingTag();

        $listing->addTag($tag);
        $listing->addTag($tag);

        self::assertCount(1, $listing->getTags());
    }

    public function testRemoveTagDetachesIt(): void
    {
        $listing = new Listing();
        $tag = new ListingTag();

        $listing->addTag($tag);
        $listing->removeTag($tag);

        self::assertCount(0, $listing->getTags());
    }

    public function testClearTagsRemovesAll(): void
    {
        $listing = new Listing();
        $listing->addTag(new ListingTag());
        $listing->addTag(new ListingTag());

        $listing->clearTags();

        self::assertCount(0, $listing->getTags());
    }

    public function testInverseSideStartsEmpty(): void
    {
        $tag = new ListingTag();

        self::assertCount(0, $tag->getListings());
    }
}
