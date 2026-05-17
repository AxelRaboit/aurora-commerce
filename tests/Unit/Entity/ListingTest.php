<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Erp\Product\Entity\Product;
use PHPUnit\Framework\TestCase;

final class ListingTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Listing())->getId());
    }

    public function testCollectionsInitialized(): void
    {
        $listing = new Listing();

        self::assertCount(0, $listing->getCategories());
        self::assertCount(0, $listing->getTags());
    }

    public function testDefaultValues(): void
    {
        $listing = new Listing();

        self::assertNull($listing->getReference());
        self::assertNull($listing->getMarketingTitle());
        self::assertNull($listing->getMarketingDescription());
        self::assertNull($listing->getFeaturedImage());
        self::assertTrue($listing->isVisibleOnShop());
        self::assertNull($listing->getSeoTitle());
        self::assertNull($listing->getSeoDescription());
    }

    public function testProductAndSlugGettersAndSetters(): void
    {
        $product = (new Product())->setName('Widget');
        $listing = (new Listing())->setProduct($product)->setSlug('widget');

        self::assertSame($product, $listing->getProduct());
        self::assertSame('widget', $listing->getSlug());
    }

    public function testGetDisplayTitleFallsBackToProductName(): void
    {
        $product = (new Product())->setName('Widget Pro');
        $listing = (new Listing())->setProduct($product);

        self::assertSame('Widget Pro', $listing->getDisplayTitle());
    }

    public function testGetDisplayTitleUsesMarketingTitleWhenSet(): void
    {
        $product = (new Product())->setName('Widget Pro');
        $listing = (new Listing())->setProduct($product)->setMarketingTitle('Premium Widget');

        self::assertSame('Premium Widget', $listing->getDisplayTitle());
    }

    public function testMarketingFields(): void
    {
        $listing = (new Listing())
            ->setMarketingTitle('Premium Widget')
            ->setMarketingDescription('Top quality widget');

        self::assertSame('Premium Widget', $listing->getMarketingTitle());
        self::assertSame('Top quality widget', $listing->getMarketingDescription());
    }

    public function testFeaturedImageGetterAndSetter(): void
    {
        $image = $this->createStub(MediaInterface::class);
        $listing = (new Listing())->setFeaturedImage($image);

        self::assertSame($image, $listing->getFeaturedImage());
    }

    public function testIsVisibleOnShopGetterAndSetter(): void
    {
        $listing = (new Listing())->setVisibleOnShop(false);

        self::assertFalse($listing->isVisibleOnShop());
    }

    public function testSeoFields(): void
    {
        $listing = (new Listing())->setSeoTitle('SEO Title')->setSeoDescription('SEO Description');

        self::assertSame('SEO Title', $listing->getSeoTitle());
        self::assertSame('SEO Description', $listing->getSeoDescription());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $listing = (new Listing())->setReference('LIST-001');

        self::assertSame('LIST-001', $listing->getReference());
    }

    public function testAddAndRemoveCategory(): void
    {
        $listing = new Listing();
        $category = $this->createStub(ListingCategoryInterface::class);

        $listing->addCategory($category);
        self::assertCount(1, $listing->getCategories());

        $listing->addCategory($category);
        self::assertCount(1, $listing->getCategories(), 'duplicate ignored');

        $listing->removeCategory($category);
        self::assertCount(0, $listing->getCategories());
    }

    public function testClearCategories(): void
    {
        $listing = new Listing();
        $listing->addCategory($this->createStub(ListingCategoryInterface::class));
        $listing->addCategory($this->createStub(ListingCategoryInterface::class));

        $listing->clearCategories();

        self::assertCount(0, $listing->getCategories());
    }

    public function testAddAndRemoveTag(): void
    {
        $listing = new Listing();
        $tag = $this->createStub(ListingTagInterface::class);

        $listing->addTag($tag);
        self::assertCount(1, $listing->getTags());

        $listing->removeTag($tag);
        self::assertCount(0, $listing->getTags());
    }

    public function testClearTags(): void
    {
        $listing = new Listing();
        $listing->addTag($this->createStub(ListingTagInterface::class));
        $listing->addTag($this->createStub(ListingTagInterface::class));

        $listing->clearTags();

        self::assertCount(0, $listing->getTags());
    }
}
