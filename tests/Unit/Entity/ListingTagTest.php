<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslation;
use PHPUnit\Framework\TestCase;

final class ListingTagTest extends TestCase
{
    public function testDefaults(): void
    {
        $tag = new ListingTag();

        self::assertSame('#6366F1', $tag->getColor());
        self::assertTrue($tag->isVisible());
        self::assertCount(0, $tag->getTranslations());
    }

    public function testSettersReturnSelf(): void
    {
        $tag = new ListingTag();

        $tag->setColor('#FF5733');
        $tag->setVisible(false);

        self::assertSame('#FF5733', $tag->getColor());
        self::assertFalse($tag->isVisible());
    }

    public function testAddTranslationKeyedByLocale(): void
    {
        $tag = new ListingTag();
        $translation = new ListingTagTranslation();
        $translation->setLocale('fr');
        $translation->setName('Promo');
        $translation->setSlug('promo');

        $tag->addTranslation($translation);

        self::assertSame($translation, $tag->getTranslation('fr'));
        self::assertSame($tag, $translation->getTag());
        self::assertCount(1, $tag->getTranslations());
    }

    public function testAddTranslationIsIdempotentByLocale(): void
    {
        $tag = new ListingTag();
        $first = new ListingTagTranslation();
        $first->setLocale('en');
        $first->setName('New');
        $first->setSlug('new');
        $tag->addTranslation($first);

        $second = new ListingTagTranslation();
        $second->setLocale('en');
        $second->setName('Different');
        $second->setSlug('different');
        $tag->addTranslation($second);

        self::assertCount(1, $tag->getTranslations());
        self::assertSame($first, $tag->getTranslation('en'));
    }

    public function testRemoveTranslation(): void
    {
        $tag = new ListingTag();
        $translation = new ListingTagTranslation();
        $translation->setLocale('en');
        $translation->setName('Sale');
        $translation->setSlug('sale');
        $tag->addTranslation($translation);

        $tag->removeTranslation($translation);

        self::assertCount(0, $tag->getTranslations());
    }
}
