<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Taxonomy\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Dto\TaxonomyInput;
use Aurora\Module\Editorial\Taxonomy\Dto\TaxonomyTermInput;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Manager\TaxonomyManager;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class TaxonomyManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private TaxonomyRepository $taxonomyRepository;
    private TaxonomyTermRepository $termRepository;
    private PostTypeRepository $postTypeRepository;
    private SettingRepository $settingRepository;
    private TaxonomyManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taxonomyRepository = $this->createMock(TaxonomyRepository::class);
        $this->termRepository = $this->createMock(TaxonomyTermRepository::class);
        $this->postTypeRepository = $this->createMock(PostTypeRepository::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->settingRepository->method('getOrDefault')->willReturn('TERM');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key): string => "tr({$key})");

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        // Real SequenceGenerator on a stubbed Connection (yields 1).
        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $this->manager = new TaxonomyManager(
            $this->entityManager,
            $this->taxonomyRepository,
            $this->termRepository,
            $this->postTypeRepository,
            new AsciiSlugger(),
            $translator,
            $auditLogger,
            new SequenceGenerator($connection),
            $this->settingRepository,
        );
    }

    public function testCreateRejectsDuplicateSlug(): void
    {
        $this->taxonomyRepository->method('findOneBySlug')->willReturn($this->makeTaxonomy(slug: 'category'));

        $this->expectException(InvalidArgumentException::class);

        $this->manager->create($this->makeTaxInput(slug: 'category'));
    }

    public function testCreateMarksUserCreatedAsNotBuiltIn(): void
    {
        // Same rule as PostType: only platform-shipped taxonomies are
        // built-in. create() must always stamp false.
        $this->taxonomyRepository->method('findOneBySlug')->willReturn(null);

        $taxonomy = $this->manager->create($this->makeTaxInput(slug: 'tag', hierarchical: false));

        self::assertFalse($taxonomy->isBuiltIn());
        self::assertSame('tag', $taxonomy->getSlug());
        self::assertFalse($taxonomy->isHierarchical());
    }

    public function testCreateAppliesTranslations(): void
    {
        $this->taxonomyRepository->method('findOneBySlug')->willReturn(null);

        $taxonomy = $this->manager->create($this->makeTaxInput(
            slug: 'category',
            translations: [
                'fr' => ['label' => 'Catégorie', 'description' => 'Desc FR'],
                'en' => ['label' => 'Category', 'description' => null],
            ],
        ));

        self::assertSame('Catégorie', $taxonomy->translate('fr')->getLabel());
        self::assertSame('Desc FR', $taxonomy->translate('fr')->getDescription());
        self::assertSame('Category', $taxonomy->translate('en')->getLabel());
        self::assertNull($taxonomy->translate('en')->getDescription());
    }

    public function testUpdateAllowsSlugChangeWhenAvailable(): void
    {
        $taxonomy = $this->makeTaxonomy(slug: 'old');
        $this->taxonomyRepository->method('findOneBySlug')->willReturn(null);

        $this->manager->update($taxonomy, $this->makeTaxInput(slug: 'new'));

        self::assertSame('new', $taxonomy->getSlug());
    }

    public function testUpdateRejectsSlugChangeWhenTaken(): void
    {
        $taxonomy = $this->makeTaxonomy(id: 1, slug: 'old');
        $this->taxonomyRepository->method('findOneBySlug')->willReturn($this->makeTaxonomy(id: 2, slug: 'taken'));

        $this->expectException(InvalidArgumentException::class);

        $this->manager->update($taxonomy, $this->makeTaxInput(slug: 'taken'));
    }

    public function testUpdateOnBuiltInIgnoresSlugAndHierarchicalChanges(): void
    {
        // Built-in taxonomies (e.g. 'category', 'tag') have a fixed
        // slug + hierarchical setting. Only translations + post-type
        // bindings are editable. Even if the input tries to swap the
        // slug, the manager silently keeps the original.
        $taxonomy = $this->makeTaxonomy(slug: 'category', hierarchical: true, builtIn: true);

        $this->manager->update($taxonomy, $this->makeTaxInput(slug: 'attempted-rename', hierarchical: false));

        self::assertSame('category', $taxonomy->getSlug(), 'built-in slug stays locked');
        self::assertTrue($taxonomy->isHierarchical(), 'built-in hierarchical flag stays locked');
    }

    public function testDeleteRefusesBuiltInTaxonomy(): void
    {
        $taxonomy = $this->makeTaxonomy(builtIn: true);

        $this->expectException(RuntimeException::class);

        $this->manager->delete($taxonomy);
    }

    public function testDeleteRemovesNonBuiltIn(): void
    {
        $taxonomy = $this->makeTaxonomy();

        $this->entityManager->expects(self::once())->method('remove')->with($taxonomy);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($taxonomy);
    }

    public function testCreateTermAssignsReferenceAndPosition(): void
    {
        // Reference = TERM-prefix + zero-padded next sequence value.
        // Position = max(siblings) + 1.
        $taxonomy = $this->makeTaxonomy(hierarchical: true);
        $sibling = $this->makeTerm(id: 5, taxonomy: $taxonomy, position: 3);
        $this->termRepository->method('findBy')->willReturn([$sibling]);

        $term = $this->manager->createTerm($taxonomy, $this->makeTermInput());

        self::assertSame('TERM-000001', $term->getReference());
        self::assertSame(4, $term->getPosition());
        self::assertSame($taxonomy, $term->getTaxonomy());
    }

    public function testCreateTermInNonHierarchicalTaxonomyIgnoresParentInput(): void
    {
        // Flat taxonomies (e.g. tag) never have a parent — silently
        // dropped even if the front somehow ships a parentId.
        $taxonomy = $this->makeTaxonomy(hierarchical: false);
        $this->termRepository->method('findBy')->willReturn([]);
        $this->termRepository->expects(self::never())->method('find');

        $term = $this->manager->createTerm($taxonomy, $this->makeTermInput(parentId: 42));

        self::assertNull($term->getParent());
    }

    public function testCreateTermRejectsParentFromAnotherTaxonomy(): void
    {
        // Security guarantee: you can't make a Category term the parent
        // of a Tag term (or vice versa).
        $taxonomy = $this->makeTaxonomy(id: 1, hierarchical: true);
        $otherTax = $this->makeTaxonomy(id: 2, hierarchical: true);
        $foreignParent = $this->makeTerm(id: 99, taxonomy: $otherTax);

        $this->termRepository->method('find')->willReturn($foreignParent);

        $this->expectException(InvalidArgumentException::class);

        $this->manager->createTerm($taxonomy, $this->makeTermInput(parentId: 99));
    }

    public function testCreateTermAppliesTranslationsWithAutoSlug(): void
    {
        // When the input ships a name but no slug, the slugger auto-
        // derives one (lower-cased ASCII).
        $taxonomy = $this->makeTaxonomy();
        $this->termRepository->method('findBy')->willReturn([]);

        $term = $this->manager->createTerm($taxonomy, $this->makeTermInput(translations: [
            'fr' => ['name' => 'Actualités du jour', 'description' => 'd'],
        ]));

        $translation = $term->translate('fr');
        self::assertSame('Actualités du jour', $translation->getName());
        self::assertSame('actualites-du-jour', $translation->getSlug(), 'slug auto-derived from name');
    }

    public function testCreateTermKeepsExplicitSlug(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $this->termRepository->method('findBy')->willReturn([]);

        $term = $this->manager->createTerm($taxonomy, $this->makeTermInput(translations: [
            'fr' => ['name' => 'News', 'slug' => 'custom-slug'],
        ]));

        self::assertSame('custom-slug', $term->translate('fr')->getSlug());
    }

    public function testDeleteTermPromotesChildrenToItsParent(): void
    {
        // Deleting a parent must NOT cascade-delete its children — the
        // subtree is preserved by re-parenting each child to the deleted
        // term's parent (or to root if the deleted term was root).
        $taxonomy = $this->makeTaxonomy(hierarchical: true);
        $grandparent = $this->makeTerm(id: 1, taxonomy: $taxonomy);
        $parent = $this->makeTerm(id: 2, taxonomy: $taxonomy);
        $parent->setParent($grandparent);
        $childA = $this->makeTerm(id: 3, taxonomy: $taxonomy);
        $childA->setParent($parent);
        $parent->getChildren()->add($childA);
        $childB = $this->makeTerm(id: 4, taxonomy: $taxonomy);
        $childB->setParent($parent);
        $parent->getChildren()->add($childB);

        $this->entityManager->expects(self::once())->method('remove')->with($parent);

        $this->manager->deleteTerm($parent);

        self::assertSame($grandparent, $childA->getParent(), 'child re-parented to deleted term grandparent');
        self::assertSame($grandparent, $childB->getParent());
    }

    public function testReorderTermsRejectsCycle(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $a = $this->makeTerm(id: 1, taxonomy: $taxonomy);
        $b = $this->makeTerm(id: 2, taxonomy: $taxonomy);
        $this->termRepository->method('findByTaxonomyOrdered')->willReturn([$a, $b]);

        $this->expectException(InvalidArgumentException::class);

        // 1 → parent 2 → parent 1 = cycle.
        $this->manager->reorderTerms($taxonomy, [
            ['id' => 1, 'parentId' => 2, 'position' => 0],
            ['id' => 2, 'parentId' => 1, 'position' => 0],
        ]);
    }

    public function testReorderTermsAppliesParentAndPosition(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $a = $this->makeTerm(id: 1, taxonomy: $taxonomy);
        $b = $this->makeTerm(id: 2, taxonomy: $taxonomy);
        $c = $this->makeTerm(id: 3, taxonomy: $taxonomy);
        $this->termRepository->method('findByTaxonomyOrdered')->willReturn([$a, $b, $c]);

        $this->manager->reorderTerms($taxonomy, [
            ['id' => 1, 'parentId' => null, 'position' => 0],
            ['id' => 2, 'parentId' => 1, 'position' => 0],
            ['id' => 3, 'parentId' => null, 'position' => 1],
        ]);

        self::assertNull($a->getParent());
        self::assertSame($a, $b->getParent());
        self::assertNull($c->getParent());
        self::assertSame(1, $c->getPosition());
    }

    public function testUpdateTermRejectsSelfNestedParent(): void
    {
        // Term can't become its own descendant — that would create a
        // cycle. Manager rejects up-front (not via reorder).
        $taxonomy = $this->makeTaxonomy(hierarchical: true);
        $parent = $this->makeTerm(id: 1, taxonomy: $taxonomy);
        $child = $this->makeTerm(id: 2, taxonomy: $taxonomy);
        $child->setParent($parent);
        $parent->getChildren()->add($child);

        // Try to move parent UNDER its own child.
        $this->termRepository->method('find')->willReturn($child);

        $this->expectException(InvalidArgumentException::class);

        $this->manager->updateTerm($parent, $this->makeTermInput(parentId: 2));
    }

    // ── Fixtures ────────────────────────────────────────────────────

    private function makeTaxonomy(int $id = 1, string $slug = 'cat', bool $hierarchical = false, bool $builtIn = false): Taxonomy
    {
        $taxonomy = new Taxonomy();
        (new ReflectionProperty(Taxonomy::class, 'id'))->setValue($taxonomy, $id);
        $taxonomy->setSlug($slug);
        $taxonomy->setHierarchical($hierarchical);
        $taxonomy->setIsBuiltIn($builtIn);

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

    /**
     * @param array<string, array{label?: string, description?: ?string}> $translations
     */
    private function makeTaxInput(
        string $slug = 'cat',
        bool $hierarchical = false,
        array $translations = [],
        array $postTypeIds = [],
    ): TaxonomyInput {
        return new TaxonomyInput(
            slug: $slug,
            hierarchical: $hierarchical,
            translations: $translations ?: ['fr' => ['label' => 'L', 'description' => null]],
            postTypeIds: $postTypeIds,
        );
    }

    /**
     * @param array<string, array{name?: ?string, slug?: ?string, description?: ?string}> $translations
     */
    private function makeTermInput(?int $parentId = null, array $translations = []): TaxonomyTermInput
    {
        return new TaxonomyTermInput(
            translations: $translations ?: ['fr' => ['name' => 'T', 'slug' => 't', 'description' => null]],
            parentId: $parentId,
        );
    }
}
