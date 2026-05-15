<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslation;
use PHPUnit\Framework\TestCase;

final class ListingTagTranslationTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ListingTagTranslation())->getId());
    }

    public function testDescriptionIsNullByDefault(): void
    {
        self::assertNull((new ListingTagTranslation())->getDescription());
    }

    public function testLocaleGetterAndSetter(): void
    {
        $translation = (new ListingTagTranslation())->setLocale('en');

        self::assertSame('en', $translation->getLocale());
    }

    public function testNameAndSlugGettersAndSetters(): void
    {
        $translation = (new ListingTagTranslation())->setName('Promo')->setSlug('promo');

        self::assertSame('Promo', $translation->getName());
        self::assertSame('promo', $translation->getSlug());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $translation = (new ListingTagTranslation())->setDescription('A description');

        self::assertSame('A description', $translation->getDescription());

        $translation->setDescription(null);
        self::assertNull($translation->getDescription());
    }

    public function testTagGetterAndSetter(): void
    {
        $tag = $this->createStub(ListingTagInterface::class);
        $translation = (new ListingTagTranslation())->setTag($tag);

        self::assertSame($tag, $translation->getTag());
    }
}
