<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Taxonomy\Serializer;

use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTranslation;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializer;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomyTermSerializerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
final class TaxonomySerializerTest extends TestCase
{
    public function testSerializeProjectsCoreFieldsAndTranslations(): void
    {
        $taxonomy = $this->makeTaxonomy(id: 7, slug: 'category', hierarchical: true);
        $this->addTaxonomyTranslation($taxonomy, 'fr', 'Catégorie', description: 'Cat.');
        $this->addTaxonomyTranslation($taxonomy, 'en', 'Category', description: null);

        $serializer = new TaxonomySerializer($this->createStub(TaxonomyTermSerializerInterface::class));

        $payload = $serializer->serialize($taxonomy);

        self::assertSame(7, $payload['id']);
        self::assertSame('category', $payload['slug']);
        self::assertTrue($payload['hierarchical']);
        self::assertSame('Catégorie', $payload['translations']['fr']['label']);
        self::assertSame('Cat.', $payload['translations']['fr']['description']);
        self::assertSame('Category', $payload['translations']['en']['label']);
        self::assertNull($payload['translations']['en']['description']);
    }

    public function testSerializeFlattensPostTypeIds(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $taxonomy->getPostTypes()->add($this->makePostType(11));
        $taxonomy->getPostTypes()->add($this->makePostType(22));

        $serializer = new TaxonomySerializer($this->createStub(TaxonomyTermSerializerInterface::class));

        self::assertSame([11, 22], $serializer->serialize($taxonomy)['postTypeIds']);
    }

    public function testSerializeExposesIsBuiltInFlag(): void
    {
        // Built-in taxonomies (category/tag) can't be deleted by the
        // user; the frontend uses this flag to disable the delete UI.
        $taxonomy = $this->makeTaxonomy(builtIn: true);

        $serializer = new TaxonomySerializer($this->createStub(TaxonomyTermSerializerInterface::class));

        self::assertTrue($serializer->serialize($taxonomy)['isBuiltIn']);
    }

    public function testSerializeFullDelegatesTermSerializationToInjectedSerializer(): void
    {
        // serializeFull adds a `terms` array; each term is delegated to
        // the term serializer's `serializeFull` (so the term-level
        // mapping logic stays in one place).
        $taxonomy = $this->makeTaxonomy(id: 7);
        $taxonomy->getTerms()->add($this->makeTerm(100));
        $taxonomy->getTerms()->add($this->makeTerm(200));

        $termSerializer = $this->createMock(TaxonomyTermSerializerInterface::class);
        $termSerializer->expects(self::exactly(2))
            ->method('serializeFull')
            ->willReturnOnConsecutiveCalls(['id' => 100], ['id' => 200]);

        $serializer = new TaxonomySerializer($termSerializer);

        $payload = $serializer->serializeFull($taxonomy);

        self::assertCount(2, $payload['terms']);
        self::assertSame(100, $payload['terms'][0]['id']);
        self::assertSame(200, $payload['terms'][1]['id']);
        // Core fields are still present (serializeFull spreads
        // serialize() over its own additions).
        self::assertSame(7, $payload['id']);
    }

    private function makeTaxonomy(int $id = 1, string $slug = 'cat', bool $hierarchical = false, bool $builtIn = false): Taxonomy
    {
        $taxonomy = new Taxonomy();
        (new ReflectionProperty(Taxonomy::class, 'id'))->setValue($taxonomy, $id);
        $taxonomy->setSlug($slug);
        $taxonomy->setHierarchical($hierarchical);
        $taxonomy->setIsBuiltIn($builtIn);

        return $taxonomy;
    }

    private function makePostType(int $id): PostType
    {
        $postType = new PostType();
        (new ReflectionProperty(PostType::class, 'id'))->setValue($postType, $id);

        return $postType;
    }

    private function makeTerm(int $id): TaxonomyTerm
    {
        $term = new TaxonomyTerm();
        (new ReflectionProperty(TaxonomyTerm::class, 'id'))->setValue($term, $id);

        return $term;
    }

    private function addTaxonomyTranslation(Taxonomy $taxonomy, string $locale, string $label, ?string $description): void
    {
        $translation = new TaxonomyTranslation();
        $translation->setLocale($locale);
        $translation->setLabel($label);
        $translation->setDescription($description);
        $taxonomy->getTranslations()->set($locale, $translation);
    }
}
