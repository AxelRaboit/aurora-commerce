<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Post\Dto\PostTypeFieldInput;
use Aurora\Module\Editorial\Post\Dto\PostTypeInput;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Entity\PostTypeField;
use Aurora\Module\Editorial\Post\Manager\PostTypeManager;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class PostTypeManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PostTypeRepository $postTypeRepository;
    private TaxonomyRepository $taxonomyRepository;
    private PostTypeManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->postTypeRepository = $this->createMock(PostTypeRepository::class);
        $this->taxonomyRepository = $this->createMock(TaxonomyRepository::class);

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

        $this->manager = new PostTypeManager(
            $this->entityManager,
            $this->postTypeRepository,
            $this->taxonomyRepository,
            $translator,
            $auditLogger,
        );
    }

    public function testCreateRejectsDuplicateSlug(): void
    {
        // Slug is the primary functional identifier (drives URLs, route
        // names). Manager must refuse a duplicate even before persistence.
        $this->postTypeRepository->method('findOneBy')->willReturn($this->makePostType(slug: 'article'));

        $this->expectException(InvalidArgumentException::class);

        $this->manager->create(new PostTypeInput(slug: 'article', label: 'Article'));
    }

    public function testCreateMarksUserCreatedTypesAsNotBuiltIn(): void
    {
        // `isBuiltIn` distinguishes the platform-shipped post types (post,
        // page) from user-created ones — only the latter can be deleted.
        // create() must always stamp false, even if the input somehow
        // hinted otherwise.
        $this->postTypeRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $postType = $this->manager->create(new PostTypeInput(slug: 'recipe', label: 'Recipe'));

        self::assertFalse($postType->isBuiltIn());
        self::assertSame('recipe', $postType->getSlug());
        self::assertSame('Recipe', $postType->getLabel());
    }

    public function testUpdateAllowsSlugChangeWhenNotTaken(): void
    {
        $postType = $this->makePostType(id: 1, slug: 'old');
        // `findOneBy` returns null for the new slug → no collision.
        $this->postTypeRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($postType, new PostTypeInput(slug: 'new', label: 'L'));

        self::assertSame('new', $postType->getSlug());
    }

    public function testUpdateRejectsSlugChangeWhenTargetTaken(): void
    {
        $postType = $this->makePostType(id: 1, slug: 'old');
        // findOneBy returns *another* existing post type with that slug.
        $this->postTypeRepository->method('findOneBy')->willReturn($this->makePostType(id: 2, slug: 'taken'));

        $this->expectException(InvalidArgumentException::class);

        $this->manager->update($postType, new PostTypeInput(slug: 'taken', label: 'L'));
    }

    public function testUpdateAllowsBuiltInTypesToKeepTheirSlugUnchecked(): void
    {
        // Built-in types can be edited (label/icon/supports) but their
        // slug is functionally locked — the manager skips the uniqueness
        // check when the input slug matches the existing one (the
        // happy-path label-only edit).
        $postType = $this->makePostType(id: 1, slug: 'post', builtIn: true);

        // findOneBy must NEVER be called (slug unchanged → no check).
        $this->postTypeRepository->expects(self::never())->method('findOneBy');

        $this->manager->update($postType, new PostTypeInput(slug: 'post', label: 'Renamed Posts'));

        self::assertSame('Renamed Posts', $postType->getLabel());
    }

    public function testDeleteRefusesBuiltInTypes(): void
    {
        $postType = $this->makePostType(builtIn: true);

        $this->expectException(RuntimeException::class);

        $this->manager->delete($postType);
    }

    public function testDeleteRefusesTypesThatStillHavePosts(): void
    {
        // Cascading delete of all posts when a post type goes away would
        // be a catastrophic admin foot-gun. Block it: the admin must
        // delete or reassign posts first.
        $postType = $this->makePostType();
        $postType->getPosts()->add(new Post());

        $this->expectException(RuntimeException::class);

        $this->manager->delete($postType);
    }

    public function testDeleteRemovesEmptyNonBuiltInType(): void
    {
        $postType = $this->makePostType();

        $this->entityManager->expects(self::once())->method('remove')->with($postType);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($postType);
    }

    public function testCreateFieldAssignsNextPosition(): void
    {
        // First field → position 0; subsequent ones → max+1. Lets the
        // frontend rely on contiguous ordering after every save.
        $postType = $this->makePostType();
        $existing = $this->makeField(id: 1, name: 'first', position: 7);
        $postType->addField($existing);

        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $field = $this->manager->createField($postType, $this->makeFieldInput(name: 'second'));

        self::assertSame(8, $field->getPosition(), 'new field gets max(position)+1');
        self::assertSame($postType, $field->getPostType());
    }

    public function testCreateFieldFirstFieldGetsPositionZero(): void
    {
        $postType = $this->makePostType();
        // No existing fields → position starts at 0 (max(-1)+1=0).

        $field = $this->manager->createField($postType, $this->makeFieldInput(name: 'a'));

        self::assertSame(0, $field->getPosition());
    }

    public function testCreateFieldRejectsDuplicateName(): void
    {
        // Field names are used as keys in `customFields` JSON and as
        // form-input names — duplicates would corrupt the payload.
        $postType = $this->makePostType();
        $postType->addField($this->makeField(id: 1, name: 'title'));

        $this->expectException(InvalidArgumentException::class);

        $this->manager->createField($postType, $this->makeFieldInput(name: 'title'));
    }

    public function testUpdateFieldAllowsKeepingTheSameName(): void
    {
        // Renaming with the same name (edit other props, name unchanged)
        // must not trigger the uniqueness check (which would self-
        // collide with the field being updated).
        $postType = $this->makePostType();
        $field = $this->makeField(id: 1, name: 'title');
        $postType->addField($field);

        // No exception expected.
        $this->manager->updateField($field, $this->makeFieldInput(name: 'title', label: 'Renamed label'));

        self::assertSame('Renamed label', $field->getLabel());
    }

    public function testUpdateFieldRejectsRenameToAlreadyUsedName(): void
    {
        $postType = $this->makePostType();
        $a = $this->makeField(id: 1, name: 'title');
        $b = $this->makeField(id: 2, name: 'body');
        $postType->addField($a);
        $postType->addField($b);

        $this->expectException(InvalidArgumentException::class);

        $this->manager->updateField($a, $this->makeFieldInput(name: 'body'));
    }

    public function testDeleteFieldRemovesAndFlushes(): void
    {
        $field = $this->makeField(id: 1, name: 'a');

        $this->entityManager->expects(self::once())->method('remove')->with($field);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->deleteField($field);
    }

    public function testReorderFieldsRewritesPositionsFromIdOrder(): void
    {
        // Drag-and-drop UX: the front ships the new order as a list of
        // ids; manager rewrites each field's position to its index in
        // that list. Unknown ids are silently skipped (defensive
        // against stale client state).
        $postType = $this->makePostType();
        $a = $this->makeField(id: 10, name: 'a', position: 0);
        $b = $this->makeField(id: 20, name: 'b', position: 1);
        $c = $this->makeField(id: 30, name: 'c', position: 2);
        $postType->addField($a);
        $postType->addField($b);
        $postType->addField($c);

        $this->entityManager->expects(self::once())->method('flush');

        // New order: c, a, b — plus an unknown id that must be skipped.
        $this->manager->reorderFields($postType, [30, 999, 10, 20]);

        self::assertSame(0, $c->getPosition());
        self::assertSame(1, $a->getPosition());
        self::assertSame(2, $b->getPosition());
    }

    public function testApplyInputSyncsTaxonomies(): void
    {
        // applyInput → syncTaxonomies diff: old (1,2) → new (2,3): 1 dropped, 3 added, 2 kept.
        $postType = $this->makePostType();
        $t1 = $this->makeTaxonomy(1);
        $t2 = $this->makeTaxonomy(2);
        $t3 = $this->makeTaxonomy(3);
        $postType->addTaxonomy($t1);
        $postType->addTaxonomy($t2);

        $this->taxonomyRepository->method('findBy')->willReturn([$t2, $t3]);
        $this->postTypeRepository->method('findOneBy')->willReturn(null);

        $this->manager->update($postType, new PostTypeInput(slug: 'x', label: 'X', taxonomyIds: [2, 3]));

        $ids = $postType->getTaxonomies()->map(fn (Taxonomy $t): ?int => $t->getId())->toArray();
        sort($ids);
        self::assertSame([2, 3], $ids);
    }

    // ── Fixtures ────────────────────────────────────────────────────

    private function makePostType(int $id = 1, string $slug = 'post', bool $builtIn = false): PostType
    {
        $postType = new PostType();
        (new ReflectionProperty(PostType::class, 'id'))->setValue($postType, $id);
        $postType->setSlug($slug);
        $postType->setLabel('PT');
        $postType->setIsBuiltIn($builtIn);

        return $postType;
    }

    private function makeField(int $id, string $name, int $position = 0): PostTypeField
    {
        $field = new PostTypeField();
        (new ReflectionProperty(PostTypeField::class, 'id'))->setValue($field, $id);
        $field->setName($name);
        $field->setLabel('L');
        $field->setType('text');
        $field->setPosition($position);

        return $field;
    }

    private function makeFieldInput(string $name = 'field', string $label = 'L'): PostTypeFieldInput
    {
        return new PostTypeFieldInput(
            name: $name,
            label: $label,
            type: 'text',
            required: false,
            translatable: true,
            options: [],
        );
    }

    private function makeTaxonomy(int $id): Taxonomy
    {
        $taxonomy = new Taxonomy();
        (new ReflectionProperty(Taxonomy::class, 'id'))->setValue($taxonomy, $id);
        $taxonomy->setSlug('t'.$id);
        $taxonomy->setHierarchical(false);

        return $taxonomy;
    }
}
