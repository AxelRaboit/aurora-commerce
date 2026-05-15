<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslation;
use PHPUnit\Framework\TestCase;

final class ListingCategoryTranslationTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ListingCategoryTranslation())->getId());
    }

    public function testDefaultValues(): void
    {
        $translation = new ListingCategoryTranslation();

        self::assertNull($translation->getDescription());
        self::assertNull($translation->getSeoTitle());
        self::assertNull($translation->getSeoDescription());
    }

    public function testLocaleGetterAndSetter(): void
    {
        $translation = (new ListingCategoryTranslation())->setLocale('fr');

        self::assertSame('fr', $translation->getLocale());
    }

    public function testNameAndSlugGettersAndSetters(): void
    {
        $translation = (new ListingCategoryTranslation())->setName('Bijoux')->setSlug('bijoux');

        self::assertSame('Bijoux', $translation->getName());
        self::assertSame('bijoux', $translation->getSlug());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $translation = (new ListingCategoryTranslation())->setDescription('Des bijoux');

        self::assertSame('Des bijoux', $translation->getDescription());

        $translation->setDescription(null);
        self::assertNull($translation->getDescription());
    }

    public function testSeoTitleGetterAndSetter(): void
    {
        $translation = (new ListingCategoryTranslation())->setSeoTitle('SEO Title');

        self::assertSame('SEO Title', $translation->getSeoTitle());
    }

    public function testSeoDescriptionGetterAndSetter(): void
    {
        $translation = (new ListingCategoryTranslation())->setSeoDescription('SEO Description');

        self::assertSame('SEO Description', $translation->getSeoDescription());
    }

    public function testCategoryGetterAndSetter(): void
    {
        $category = $this->createStub(ListingCategoryInterface::class);
        $translation = (new ListingCategoryTranslation())->setCategory($category);

        self::assertSame($category, $translation->getCategory());
    }
}
