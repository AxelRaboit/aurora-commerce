<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Block\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Notes\Block\Dto\BlockInput;
use Aurora\Module\Notes\Block\Dto\BlockNoteInput;
use Aurora\Module\Notes\Block\Entity\BlockNote;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use Aurora\Module\Notes\Block\Manager\BlockNoteManager;
use Aurora\Module\Notes\Block\Repository\BlockNoteRepository;
use Aurora\Module\Notes\Block\Service\BlockImageService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;

#[AllowMockObjectsWithoutExpectations]
final class BlockNoteManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BlockNoteRepository $noteRepository;
    private BlockNoteManager $manager;
    private string $imageDir;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->noteRepository = $this->createMock(BlockNoteRepository::class);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->imageDir = sys_get_temp_dir().'/aurora-block-notes-images-'.bin2hex(random_bytes(4));

        $this->manager = new BlockNoteManager(
            $this->entityManager,
            $this->noteRepository,
            $auditLogger,
            new BlockImageService($this->imageDir),
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->imageDir)) {
            (new Filesystem())->remove($this->imageDir);
        }
    }

    public function testCreateAssignsUserAndPersists(): void
    {
        $user = $this->makeUser();
        $input = new BlockNoteInput(title: 'Hello', tags: ['foo']);

        $this->noteRepository->method('findMaxPositionForUserAndParent')->willReturn(null);
        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $note = $this->manager->create($user, $input);

        self::assertSame($user, $note->getUser());
        self::assertSame('Hello', $note->getTitle());
        self::assertSame(['foo'], $note->getTags());
        self::assertSame(0, $note->getPosition(), 'first note in empty parent → position 0');
        self::assertNull($note->getParent());
        self::assertSame([], $note->getBlocks(), 'no blocks input → empty blocks list (null means untouched, only relevant on update)');
    }

    public function testCreateAssignsNextPositionForSibling(): void
    {
        $user = $this->makeUser();
        $input = new BlockNoteInput(title: 'Sibling');

        $this->noteRepository->method('findMaxPositionForUserAndParent')->willReturn(4);

        $note = $this->manager->create($user, $input);

        self::assertSame(5, $note->getPosition());
    }

    public function testCreateRespectsExplicitPosition(): void
    {
        $user = $this->makeUser();
        $input = new BlockNoteInput(title: 'Pinned', position: 99);

        $note = $this->manager->create($user, $input);

        self::assertSame(99, $note->getPosition());
    }

    public function testCreateNormalizesBlocksToEditorJsShape(): void
    {
        $user = $this->makeUser();
        $input = new BlockNoteInput(
            title: 'with blocks',
            blocks: [
                new BlockInput(type: 'paragraph', data: ['text' => 'hello'], id: 'b1'),
                new BlockInput(type: 'heading', data: ['text' => 'Title', 'level' => 2]),
            ],
        );
        $this->noteRepository->method('findMaxPositionForUserAndParent')->willReturn(null);

        $note = $this->manager->create($user, $input);
        $blocks = $note->getBlocks();

        self::assertCount(2, $blocks);
        self::assertSame(['id' => 'b1', 'type' => 'paragraph', 'data' => ['text' => 'hello']], $blocks[0]);
        // No id supplied → key omitted (Editor.js generates one on first
        // render anyway). Keeps the JSON shape minimal.
        self::assertArrayNotHasKey('id', $blocks[1]);
        self::assertSame('heading', $blocks[1]['type']);
    }

    public function testUpdateAppliesInputAndFlushes(): void
    {
        $user = $this->makeUser();
        $note = new BlockNote();
        $note->setUser($user);
        $note->setTitle('old');

        $input = new BlockNoteInput(title: 'new', tags: ['a', 'b'], blocks: [
            new BlockInput(type: 'paragraph', data: ['text' => 'updated']),
        ]);

        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($note, $input);

        self::assertSame('new', $note->getTitle());
        self::assertSame(['a', 'b'], $note->getTags());
        self::assertCount(1, $note->getBlocks());
        self::assertSame('paragraph', $note->getBlocks()[0]['type']);
    }

    public function testUpdateKeepsExistingBlocksWhenInputBlocksIsNull(): void
    {
        // null blocks = "metadata-only update": should NOT touch the
        // existing blocks list.
        $user = $this->makeUser();
        $note = new BlockNote();
        $note->setUser($user);
        $note->setBlocks([
            ['type' => 'paragraph', 'data' => ['text' => 'kept']],
        ]);

        $input = new BlockNoteInput(title: 'only metadata', blocks: null);
        $this->manager->update($note, $input);

        self::assertCount(1, $note->getBlocks());
        self::assertSame(['text' => 'kept'], $note->getBlocks()[0]['data']);
    }

    public function testUpdateClearsBlocksWhenInputBlocksIsEmpty(): void
    {
        // Empty array = "clear" (distinct from null).
        $user = $this->makeUser();
        $note = new BlockNote();
        $note->setUser($user);
        $note->setBlocks([
            ['type' => 'paragraph', 'data' => ['text' => 'remove me']],
        ]);

        $this->manager->update($note, new BlockNoteInput(blocks: []));

        self::assertSame([], $note->getBlocks());
    }

    public function testUpdateResolvesParentFromRepo(): void
    {
        $user = $this->makeUser();
        $note = new BlockNote();
        $note->setUser($user);
        $parent = new BlockNote();

        $this->noteRepository->expects(self::once())->method('findOneByUserAndId')->with($user, 42)->willReturn($parent);

        $this->manager->update($note, new BlockNoteInput(parentId: 42));

        self::assertSame($parent, $note->getParent());
    }

    public function testUpdateClearsParentWhenInputParentIdIsNull(): void
    {
        $user = $this->makeUser();
        $parent = new BlockNote();
        $note = new BlockNote();
        $note->setUser($user);
        $note->setParent($parent);

        $this->manager->update($note, new BlockNoteInput(parentId: null));

        self::assertNull($note->getParent());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $note = new BlockNote();
        $note->setUser($this->makeUser());

        $this->entityManager->expects(self::once())->method('remove')->with($note);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($note);
    }

    public function testMoveUpdatesParent(): void
    {
        $note = new BlockNote();
        $parent = new BlockNote();

        $this->entityManager->expects(self::once())->method('flush');

        $this->manager->move($note, $parent);

        self::assertSame($parent, $note->getParent());
    }

    public function testReorderAssignsParentAndPositionFromEntries(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(10);
        $b = $this->makeNoteWithId(20);
        $c = $this->makeNoteWithId(30);

        $this->noteRepository->method('findBy')->willReturn([$a, $b, $c]);
        $this->entityManager->expects(self::once())->method('flush');

        // 10 stays root pos 0; 20 becomes child of 10 at pos 0; 30 stays root pos 1.
        $this->manager->reorder($user, [
            ['id' => 10, 'parentId' => null, 'position' => 0],
            ['id' => 20, 'parentId' => 10, 'position' => 0],
            ['id' => 30, 'parentId' => null, 'position' => 1],
        ]);

        self::assertNull($a->getParent());
        self::assertSame($a, $b->getParent());
        self::assertNull($c->getParent());
        self::assertSame(0, $a->getPosition());
        self::assertSame(0, $b->getPosition());
        self::assertSame(1, $c->getPosition());
    }

    public function testReorderRejectsCycle(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(10);
        $b = $this->makeNoteWithId(20);

        $this->noteRepository->method('findBy')->willReturn([$a, $b]);

        $this->expectException(InvalidArgumentException::class);

        $this->manager->reorder($user, [
            ['id' => 10, 'parentId' => 20, 'position' => 0],
            ['id' => 20, 'parentId' => 10, 'position' => 0],
        ]);
    }

    public function testReorderOnEmptyListIsNoop(): void
    {
        $this->noteRepository->expects(self::never())->method('findBy');
        $this->entityManager->expects(self::never())->method('flush');

        $this->manager->reorder($this->makeUser(), []);
    }

    public function testTagCountsDelegatesToRepository(): void
    {
        $user = $this->makeUser();
        $this->noteRepository->expects(self::once())->method('findTagCountsForUser')->with($user)->willReturn(['a' => 2, 'b' => 1]);

        self::assertSame(['a' => 2, 'b' => 1], $this->manager->tagCounts($user));
    }

    public function testRenameTagRewritesOccurrencesAcrossNotes(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(1);
        $a->setTags(['old', 'kept']);
        $b = $this->makeNoteWithId(2);
        $b->setTags(['other']);
        $c = $this->makeNoteWithId(3);
        $c->setTags(['old']);

        $this->noteRepository->method('findAllForUser')->willReturn([$a, $b, $c]);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $affected = $this->manager->renameTag($user, 'old', 'new');

        self::assertSame(2, $affected);
        self::assertSame(['new', 'kept'], $a->getTags());
        self::assertSame(['other'], $b->getTags());
        self::assertSame(['new'], $c->getTags());
    }

    public function testRenameTagDedupesWhenTargetAlreadyPresent(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(1);
        $a->setTags(['old', 'new', 'kept']);

        $this->noteRepository->method('findAllForUser')->willReturn([$a]);

        $affected = $this->manager->renameTag($user, 'old', 'new');

        self::assertSame(1, $affected);
        self::assertSame(['new', 'kept'], $a->getTags(), 'duplicate target dropped, order preserved');
    }

    public function testRenameTagNoopWhenSourceAndTargetEqual(): void
    {
        $user = $this->makeUser();
        $this->noteRepository->expects(self::never())->method('findAllForUser');
        $this->entityManager->expects(self::never())->method('flush');

        self::assertSame(0, $this->manager->renameTag($user, 'same', 'same'));
        self::assertSame(0, $this->manager->renameTag($user, '', 'x'));
        self::assertSame(0, $this->manager->renameTag($user, 'x', ''));
    }

    public function testMergeTagsCollapsesMultipleSourcesIntoTarget(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(1);
        $a->setTags(['draft', 'wip']);
        $b = $this->makeNoteWithId(2);
        $b->setTags(['done']);
        $c = $this->makeNoteWithId(3);
        $c->setTags(['wip', 'in-progress']);

        $this->noteRepository->method('findAllForUser')->willReturn([$a, $b, $c]);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $affected = $this->manager->mergeTags($user, ['draft', 'wip', 'in-progress'], 'todo');

        self::assertSame(2, $affected);
        self::assertSame(['todo'], $a->getTags());
        self::assertSame(['done'], $b->getTags());
        self::assertSame(['todo'], $c->getTags());
    }

    public function testRemoveTagStripsItFromEveryNote(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(1);
        $a->setTags(['gone', 'kept']);
        $b = $this->makeNoteWithId(2);
        $b->setTags(['kept']);
        $c = $this->makeNoteWithId(3);
        $c->setTags(['gone']);

        $this->noteRepository->method('findAllForUser')->willReturn([$a, $b, $c]);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $affected = $this->manager->removeTag($user, 'gone');

        self::assertSame(2, $affected);
        self::assertSame(['kept'], $a->getTags());
        self::assertSame(['kept'], $b->getTags());
        self::assertSame([], $c->getTags());
    }

    public function testSearchContentMatchesAnyTextualPayload(): void
    {
        // Recursive scan: needle can hide anywhere inside `data` (Editor.js
        // shapes differ per tool — paragraph.text, list.items[*].content,
        // table.content[][], callout.message, …).
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(1);
        $a->setBlocks([
            ['type' => 'paragraph', 'data' => ['text' => 'Hello <b>WORLD</b>']],
        ]);
        $b = $this->makeNoteWithId(2);
        $b->setBlocks([
            ['type' => 'list', 'data' => ['items' => [['content' => 'first'], ['content' => 'second']]]],
        ]);
        $c = $this->makeNoteWithId(3);
        $c->setTitle('Has the WoRLd in its title');

        $this->noteRepository->method('findAllForUser')->willReturn([$a, $b, $c]);

        $matches = $this->manager->searchContent($user, 'world');

        sort($matches);
        self::assertSame([1, 3], $matches, 'a (in <b> tag stripped) + c (title) match "world"; b is unrelated');
    }

    public function testSearchContentReturnsEmptyOnBlankQuery(): void
    {
        $this->noteRepository->expects(self::never())->method('findAllForUser');

        self::assertSame([], $this->manager->searchContent($this->makeUser(), '   '));
    }

    public function testUpdateRemovesOrphanedImagesWhenBlockDisappears(): void
    {
        $user = $this->makeUser(99);
        $note = new BlockNote();
        $note->setUser($user);
        $note->setBlocks([
            ['type' => 'image', 'data' => ['file' => ['url' => '/x', 'filename' => 'keep.png']]],
            ['type' => 'image', 'data' => ['file' => ['url' => '/x', 'filename' => 'drop.png']]],
        ]);
        $this->prepareImageFile($user, 'keep.png');
        $this->prepareImageFile($user, 'drop.png');

        $input = new BlockNoteInput(
            title: 'with images',
            blocks: [
                new BlockInput(type: 'image', data: ['file' => ['url' => '/x', 'filename' => 'keep.png']]),
            ],
        );

        $this->manager->update($note, $input);

        self::assertFileExists($this->imageDir.'/99/keep.png');
        self::assertFileDoesNotExist($this->imageDir.'/99/drop.png');
    }

    public function testUpdateKeepsImageWhenSameFilenameIsStillReferenced(): void
    {
        // A block can be reordered without its image being touched. The
        // diff must compare by filename, not block identity.
        $user = $this->makeUser(99);
        $note = new BlockNote();
        $note->setUser($user);
        $note->setBlocks([
            ['type' => 'paragraph', 'data' => ['text' => 'top']],
            ['type' => 'image', 'data' => ['file' => ['url' => '/x', 'filename' => 'pic.png']]],
        ]);
        $this->prepareImageFile($user, 'pic.png');

        $input = new BlockNoteInput(blocks: [
            new BlockInput(type: 'image', data: ['file' => ['url' => '/x', 'filename' => 'pic.png']]),
            new BlockInput(type: 'paragraph', data: ['text' => 'bottom']),
        ]);

        $this->manager->update($note, $input);

        self::assertFileExists($this->imageDir.'/99/pic.png');
    }

    public function testUpdateIgnoresImageBlocksWithoutFilename(): void
    {
        // External-URL image (uploadByUrl) → `data.file.filename` is not
        // set, so it has no per-user upload to clean. Must not crash.
        $user = $this->makeUser(99);
        $note = new BlockNote();
        $note->setUser($user);
        $note->setBlocks([
            ['type' => 'image', 'data' => ['file' => ['url' => 'https://example.com/foo.png']]],
        ]);

        $this->manager->update($note, new BlockNoteInput(blocks: []));

        self::assertSame([], $note->getBlocks());
    }

    public function testDeleteRemovesEveryReferencedImage(): void
    {
        $user = $this->makeUser(99);
        $note = new BlockNote();
        $note->setUser($user);
        $note->setBlocks([
            ['type' => 'image', 'data' => ['file' => ['url' => '/x', 'filename' => 'one.png']]],
            ['type' => 'image', 'data' => ['file' => ['url' => '/x', 'filename' => 'two.png']]],
        ]);
        $this->prepareImageFile($user, 'one.png');
        $this->prepareImageFile($user, 'two.png');

        $this->manager->delete($note);

        self::assertFileDoesNotExist($this->imageDir.'/99/one.png');
        self::assertFileDoesNotExist($this->imageDir.'/99/two.png');
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function makeNoteWithId(int $id): BlockNoteInterface
    {
        $note = new BlockNote();
        (new ReflectionProperty(BlockNote::class, 'id'))->setValue($note, $id);

        return $note;
    }

    private function prepareImageFile(User $user, string $filename): void
    {
        $userDir = $this->imageDir.'/'.$user->getId();
        if (!is_dir($userDir)) {
            mkdir($userDir, 0o755, true);
        }
        file_put_contents($userDir.'/'.$filename, 'fake-image-bytes');
    }
}
