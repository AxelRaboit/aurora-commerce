<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation;
use PHPUnit\Framework\TestCase;

final class TaxonomyTermTranslationTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new TaxonomyTermTranslation())->getId());
    }

    public function testLocaleGetterAndSetter(): void
    {
        $translation = (new TaxonomyTermTranslation())->setLocale('fr');

        self::assertSame('fr', $translation->getLocale());
    }

    public function testNameGetterAndSetter(): void
    {
        $translation = (new TaxonomyTermTranslation())->setName('Actualités');

        self::assertSame('Actualités', $translation->getName());
    }

    public function testSlugGetterAndSetter(): void
    {
        $translation = (new TaxonomyTermTranslation())->setSlug('actualites');

        self::assertSame('actualites', $translation->getSlug());
    }

    public function testDescriptionIsNullByDefault(): void
    {
        self::assertNull((new TaxonomyTermTranslation())->getDescription());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $translation = (new TaxonomyTermTranslation())->setDescription('Toutes les actualités.');

        self::assertSame('Toutes les actualités.', $translation->getDescription());

        $translation->setDescription(null);
        self::assertNull($translation->getDescription());
    }

    public function testTermGetterAndSetter(): void
    {
        $term = $this->createStub(TaxonomyTermInterface::class);
        $translation = (new TaxonomyTermTranslation())->setTerm($term);

        self::assertSame($term, $translation->getTerm());
    }

    public function testSettersReturnSelf(): void
    {
        $translation = new TaxonomyTermTranslation();

        self::assertSame($translation, $translation->setLocale('en'));
        self::assertSame($translation, $translation->setName('n'));
        self::assertSame($translation, $translation->setSlug('s'));
        self::assertSame($translation, $translation->setDescription('d'));
        self::assertSame($translation, $translation->setTerm($this->createStub(TaxonomyTermInterface::class)));
    }
}
