<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTranslation;
use PHPUnit\Framework\TestCase;

final class TaxonomyTranslationTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new TaxonomyTranslation())->getId());
    }

    public function testDescriptionIsNullByDefault(): void
    {
        self::assertNull((new TaxonomyTranslation())->getDescription());
    }

    public function testLocaleGetterAndSetter(): void
    {
        $translation = (new TaxonomyTranslation())->setLocale('en');

        self::assertSame('en', $translation->getLocale());
    }

    public function testLabelGetterAndSetter(): void
    {
        $translation = (new TaxonomyTranslation())->setLabel('Categories');

        self::assertSame('Categories', $translation->getLabel());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $translation = (new TaxonomyTranslation())->setDescription('Post categories');

        self::assertSame('Post categories', $translation->getDescription());

        $translation->setDescription(null);
        self::assertNull($translation->getDescription());
    }

    public function testTaxonomyGetterAndSetter(): void
    {
        $taxonomy = new Taxonomy();
        $translation = (new TaxonomyTranslation())->setTaxonomy($taxonomy);

        self::assertSame($taxonomy, $translation->getTaxonomy());
    }
}
