<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Taxonomy\Serializer;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomyTermSerializer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
final class TaxonomyTermSerializerTest extends TestCase
{
    private TaxonomyTermSerializer $serializer;

    protected function setUp(): void
    {
        $localeContext = $this->createStub(LocaleContextInterface::class);
        $localeContext->method('getDefaultLocale')->willReturn('fr');

        $this->serializer = new TaxonomyTermSerializer($localeContext);
    }

    public function testSerializeUsesDefaultLocaleWhenNoneRequested(): void
    {
        $taxonomy = $this->makeTaxonomy(id: 1, slug: 'cat');
        $term = $this->makeTerm(id: 10, taxonomy: $taxonomy, position: 3);
        $this->addTranslation($term, 'fr', 'Catégorie FR', 'categorie-fr');
        $this->addTranslation($term, 'en', 'Category EN', 'category-en');

        $payload = $this->serializer->serialize($term);

        self::assertSame(10, $payload['id']);
        self::assertSame(1, $payload['taxonomyId']);
        self::assertSame('cat', $payload['taxonomySlug']);
        self::assertSame(3, $payload['position']);
        self::assertSame('Catégorie FR', $payload['name'], 'default locale (fr) translation picked');
        self::assertSame('categorie-fr', $payload['slug']);
    }

    public function testSerializePicksExplicitLocaleOverDefault(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $term = $this->makeTerm(id: 1, taxonomy: $taxonomy);
        $this->addTranslation($term, 'fr', 'FR name');
        $this->addTranslation($term, 'en', 'EN name');

        $payload = $this->serializer->serialize($term, 'en');

        self::assertSame('EN name', $payload['name']);
    }

    public function testSerializeFallsBackToFirstTranslationWhenRequestedLocaleMissing(): void
    {
        // Requested 'es' but only 'fr' exists — must not return null
        // (the frontend would render an empty chip).
        $taxonomy = $this->makeTaxonomy();
        $term = $this->makeTerm(id: 1, taxonomy: $taxonomy);
        $this->addTranslation($term, 'fr', 'Available');

        $payload = $this->serializer->serialize($term, 'es');

        self::assertSame('Available', $payload['name']);
    }

    public function testSerializeNameAndSlugAreNullWhenNoTranslations(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $term = $this->makeTerm(id: 1, taxonomy: $taxonomy);

        $payload = $this->serializer->serialize($term);

        self::assertNull($payload['name']);
        self::assertNull($payload['slug']);
        self::assertNull($payload['description']);
    }

    public function testSerializeIncludesParentIdWhenTermHasParent(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $parent = $this->makeTerm(id: 50, taxonomy: $taxonomy);
        $child = $this->makeTerm(id: 51, taxonomy: $taxonomy);
        $child->setParent($parent);

        self::assertSame(50, $this->serializer->serialize($child)['parentId']);
        self::assertNull($this->serializer->serialize($parent)['parentId']);
    }

    public function testSerializeFullEmitsTranslationsKeyedByLocale(): void
    {
        $taxonomy = $this->makeTaxonomy(id: 5, slug: 'tag');
        $term = $this->makeTerm(id: 100, taxonomy: $taxonomy, position: 7);
        $this->addTranslation($term, 'fr', name: 'Étiquette', slug: 'etiquette', description: 'Description FR');
        $this->addTranslation($term, 'en', name: 'Tag', slug: 'tag', description: null);

        $payload = $this->serializer->serializeFull($term);

        self::assertSame(100, $payload['id']);
        self::assertSame(5, $payload['taxonomyId']);
        self::assertSame('tag', $payload['taxonomySlug']);
        self::assertSame(7, $payload['position']);
        // No top-level name/slug — those live under their locale.
        self::assertArrayNotHasKey('name', $payload);
        self::assertSame('Étiquette', $payload['translations']['fr']['name']);
        self::assertSame('Description FR', $payload['translations']['fr']['description']);
        self::assertSame('Tag', $payload['translations']['en']['name']);
        self::assertNull($payload['translations']['en']['description']);
    }

    // ── Fixtures ────────────────────────────────────────────────────

    private function makeTaxonomy(int $id = 1, string $slug = 'cat'): Taxonomy
    {
        $taxonomy = new Taxonomy();
        (new ReflectionProperty(Taxonomy::class, 'id'))->setValue($taxonomy, $id);
        $taxonomy->setSlug($slug);
        $taxonomy->setHierarchical(false);

        return $taxonomy;
    }

    private function makeTerm(int $id, Taxonomy $taxonomy, int $position = 0): TaxonomyTerm
    {
        $term = new TaxonomyTerm();
        (new ReflectionProperty(TaxonomyTerm::class, 'id'))->setValue($term, $id);
        $term->setTaxonomy($taxonomy);
        $term->setPosition($position);

        return $term;
    }

    private function addTranslation(TaxonomyTerm $term, string $locale, string $name, string $slug = 'slug', ?string $description = null): void
    {
        $translation = new TaxonomyTermTranslation();
        $translation->setLocale($locale);
        $translation->setName($name);
        $translation->setSlug($slug);
        $translation->setDescription($description);
        $term->getTranslations()->set($locale, $translation);
    }
}
