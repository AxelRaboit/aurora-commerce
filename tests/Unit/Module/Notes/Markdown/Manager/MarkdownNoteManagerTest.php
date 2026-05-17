<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Markdown\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Notes\Markdown\Dto\MarkdownNoteInput;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Notes\Markdown\Manager\MarkdownNoteManager;
use Aurora\Module\Notes\Markdown\Repository\MarkdownNoteRepository;
use Aurora\Module\Notes\Markdown\Service\MarkdownNoteImageService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;

#[AllowMockObjectsWithoutExpectations]
final class MarkdownNoteManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MarkdownNoteRepository $markdownNoteRepository;
    private MarkdownNoteManager $markdownNoteManager;
    private string $imageDir;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->markdownNoteRepository = $this->createMock(MarkdownNoteRepository::class);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->imageDir = sys_get_temp_dir().'/aurora-notes-images-'.bin2hex(random_bytes(4));

        $this->markdownNoteManager = new MarkdownNoteManager(
            $this->entityManager,
            $this->markdownNoteRepository,
            $auditLogger,
            new MarkdownNoteImageService($this->imageDir),
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->imageDir)) {
            (new Filesystem())->remove($this->imageDir);
        }
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $r = new ReflectionProperty(User::class, 'id');
        $r->setValue($user, $id);

        return $user;
    }

    public function testCreateAssignsUserAndPersists(): void
    {
        $user = $this->makeUser();
        $input = new MarkdownNoteInput(title: 'Hello', content: '# Hello', tags: ['foo']);

        $this->markdownNoteRepository->method('findMaxPositionForUserAndParent')->willReturn(null);
        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $note = $this->markdownNoteManager->create($user, $input);

        self::assertSame($user, $note->getUser());
        self::assertSame('Hello', $note->getTitle());
        self::assertSame('# Hello', $note->getContent());
        self::assertSame(['foo'], $note->getTags());
        self::assertSame(0, $note->getPosition(), 'first note in empty parent → position 0');
        self::assertNull($note->getParent());
    }

    public function testCreateAssignsNextPositionForSibling(): void
    {
        $user = $this->makeUser();
        $input = new MarkdownNoteInput(title: 'Sibling');

        $this->markdownNoteRepository->method('findMaxPositionForUserAndParent')->willReturn(4);

        $note = $this->markdownNoteManager->create($user, $input);

        self::assertSame(5, $note->getPosition());
    }

    public function testCreateRespectsExplicitPosition(): void
    {
        $user = $this->makeUser();
        $input = new MarkdownNoteInput(title: 'Pinned', position: 99);

        $note = $this->markdownNoteManager->create($user, $input);

        self::assertSame(99, $note->getPosition());
    }

    public function testUpdateAppliesInputAndFlushes(): void
    {
        $user = $this->makeUser();
        $note = new MarkdownNote();
        $note->setUser($user);
        $note->setTitle('old');

        $input = new MarkdownNoteInput(title: 'new', content: 'updated body', tags: ['a', 'b']);

        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->markdownNoteManager->update($note, $input);

        self::assertSame('new', $note->getTitle());
        self::assertSame('updated body', $note->getContent());
        self::assertSame(['a', 'b'], $note->getTags());
    }

    public function testUpdateResolvesParentFromRepo(): void
    {
        $user = $this->makeUser();
        $note = new MarkdownNote();
        $note->setUser($user);
        $parent = new MarkdownNote();

        $this->markdownNoteRepository->expects(self::once())->method('findOneByUserAndId')->with($user, 42)->willReturn($parent);

        $input = new MarkdownNoteInput(parentId: 42);
        $this->markdownNoteManager->update($note, $input);

        self::assertSame($parent, $note->getParent());
    }

    public function testUpdateClearsParentWhenInputParentIdIsNull(): void
    {
        $user = $this->makeUser();
        $parent = new MarkdownNote();
        $note = new MarkdownNote();
        $note->setUser($user);
        $note->setParent($parent);

        $input = new MarkdownNoteInput(parentId: null);
        $this->markdownNoteManager->update($note, $input);

        self::assertNull($note->getParent());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $note = new MarkdownNote();
        $note->setUser($this->makeUser());

        $this->entityManager->expects(self::once())->method('remove')->with($note);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->markdownNoteManager->delete($note);
    }

    public function testMoveUpdatesParent(): void
    {
        $note = new MarkdownNote();
        $parent = new MarkdownNote();

        $this->entityManager->expects(self::once())->method('flush');

        $this->markdownNoteManager->move($note, $parent);

        self::assertSame($parent, $note->getParent());
    }

    public function testReorderAssignsParentAndPositionFromEntries(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(10);
        $b = $this->makeNoteWithId(20);
        $c = $this->makeNoteWithId(30);

        $this->markdownNoteRepository->method('findBy')->willReturn([$a, $b, $c]);
        $this->entityManager->expects(self::once())->method('flush');

        // 10 stays root pos 0; 20 becomes child of 10 at pos 0; 30 stays root pos 1
        $this->markdownNoteManager->reorder($user, [
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

        $this->markdownNoteRepository->method('findBy')->willReturn([$a, $b]);

        $this->expectException(InvalidArgumentException::class);

        // 10's parent is 20, 20's parent is 10 → cycle
        $this->markdownNoteManager->reorder($user, [
            ['id' => 10, 'parentId' => 20, 'position' => 0],
            ['id' => 20, 'parentId' => 10, 'position' => 0],
        ]);
    }

    public function testReorderOnEmptyListIsNoop(): void
    {
        $this->markdownNoteRepository->expects(self::never())->method('findBy');
        $this->entityManager->expects(self::never())->method('flush');

        $this->markdownNoteManager->reorder($this->makeUser(), []);
    }

    public function testUpdateRenamesWikiLinksInOtherNotesWhenTitleChanges(): void
    {
        $user = $this->makeUser();
        $note = $this->makeNoteWithId(1);
        $note->setUser($user);
        $note->setTitle('Old Title');

        $other = $this->makeNoteWithId(2);
        $other->setContent('See [[Old Title]] for details.');

        $this->markdownNoteRepository->expects(self::once())->method('findAllWithContentForUser')->with($user)->willReturn([$note, $other]);

        $this->markdownNoteManager->update($note, new MarkdownNoteInput(title: 'New Title'));

        self::assertSame('See [[New Title]] for details.', $other->getContent());
    }

    public function testUpdateDoesNotRenameWhenOldTitleWasNull(): void
    {
        $user = $this->makeUser();
        $note = $this->makeNoteWithId(1);
        $note->setUser($user);
        $note->setTitle(null);

        $this->markdownNoteRepository->expects(self::never())->method('findAllWithContentForUser');

        $this->markdownNoteManager->update($note, new MarkdownNoteInput(title: 'Brand new'));
    }

    public function testBacklinksReturnsNotesContainingTheWikiLink(): void
    {
        $user = $this->makeUser();
        $target = $this->makeNoteWithId(10);
        $target->setTitle('My Page');

        $linker = $this->makeNoteWithId(11);
        $linker->setTitle('A');
        $linker->setContent('Reference [[my page]] here.');

        $unrelated = $this->makeNoteWithId(12);
        $unrelated->setTitle('B');
        $unrelated->setContent('no link at all');

        $self = $target;

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$target, $linker, $unrelated]);

        $results = $this->markdownNoteManager->backlinks($user, $target);

        self::assertCount(1, $results);
        self::assertSame(11, $results[0]['id']);
        self::assertSame('A', $results[0]['title']);
    }

    public function testBacklinksReturnsEmptyForUntitledNote(): void
    {
        $user = $this->makeUser();
        $target = $this->makeNoteWithId(10);
        $target->setTitle(null);

        $this->markdownNoteRepository->expects(self::never())->method('findAllWithContentForUser');

        self::assertSame([], $this->markdownNoteManager->backlinks($user, $target));
    }

    public function testUnlinkedMentionsExcludesLinkedReferences(): void
    {
        $user = $this->makeUser();
        $target = $this->makeNoteWithId(10);
        $target->setTitle('Foo');

        $linker = $this->makeNoteWithId(11);
        $linker->setTitle('A');
        $linker->setContent('See [[foo]] for details.');

        $mentioner = $this->makeNoteWithId(12);
        $mentioner->setTitle('B');
        $mentioner->setContent('I love foo but not linked.');

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$target, $linker, $mentioner]);

        $results = $this->markdownNoteManager->unlinkedMentions($user, $target);

        self::assertCount(1, $results);
        self::assertSame(12, $results[0]['id']);
    }

    public function testGraphBuildsNodesAndEdgesFromWikiLinks(): void
    {
        $user = $this->makeUser();

        $a = $this->makeNoteWithId(1);
        $a->setTitle('Alpha');
        $a->setContent('Links to [[Beta]] and [[Gamma#section]].');

        $b = $this->makeNoteWithId(2);
        $b->setTitle('Beta');
        $b->setContent('No outgoing links.');

        $c = $this->makeNoteWithId(3);
        $c->setTitle('Gamma');
        $c->setContent('Refers back to [[Alpha]] and unknown [[Delta]].');

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$a, $b, $c]);

        $graph = $this->markdownNoteManager->graph($user);

        self::assertCount(3, $graph['nodes']);
        $edges = $graph['edges'];
        self::assertCount(3, $edges, 'Alpha→Beta, Alpha→Gamma, Gamma→Alpha (Delta unresolved, dropped)');
        self::assertContains(['source' => 1, 'target' => 2], $edges);
        self::assertContains(['source' => 1, 'target' => 3], $edges);
        self::assertContains(['source' => 3, 'target' => 1], $edges);
    }

    public function testGraphIgnoresSelfLinks(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(1);
        $a->setTitle('Self');
        $a->setContent('I reference [[Self]] for fun.');

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$a]);

        $graph = $this->markdownNoteManager->graph($user);

        self::assertSame([], $graph['edges']);
    }

    private function makeNoteWithId(int $id): MarkdownNoteInterface
    {
        $note = new MarkdownNote();
        $r = new ReflectionProperty(MarkdownNote::class, 'id');
        $r->setValue($note, $id);

        return $note;
    }

    public function testTagCountsDelegatesToRepository(): void
    {
        $user = $this->makeUser();
        $this->markdownNoteRepository->expects(self::once())->method('findTagCountsForUser')->with($user)->willReturn(['a' => 2, 'b' => 1]);

        self::assertSame(['a' => 2, 'b' => 1], $this->markdownNoteManager->tagCounts($user));
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

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$a, $b, $c]);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $affected = $this->markdownNoteManager->renameTag($user, 'old', 'new');

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

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$a]);

        $affected = $this->markdownNoteManager->renameTag($user, 'old', 'new');

        self::assertSame(1, $affected);
        self::assertSame(['new', 'kept'], $a->getTags(), 'duplicate target dropped, order preserved');
    }

    public function testRenameTagNoopWhenSourceAndTargetEqual(): void
    {
        $user = $this->makeUser();
        $this->markdownNoteRepository->expects(self::never())->method('findAllWithContentForUser');
        $this->entityManager->expects(self::never())->method('flush');

        self::assertSame(0, $this->markdownNoteManager->renameTag($user, 'same', 'same'));
        self::assertSame(0, $this->markdownNoteManager->renameTag($user, '', 'x'));
        self::assertSame(0, $this->markdownNoteManager->renameTag($user, 'x', ''));
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

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$a, $b, $c]);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $affected = $this->markdownNoteManager->mergeTags($user, ['draft', 'wip', 'in-progress'], 'todo');

        self::assertSame(2, $affected);
        self::assertSame(['todo'], $a->getTags(), 'draft+wip collapsed to a single todo');
        self::assertSame(['done'], $b->getTags());
        self::assertSame(['todo'], $c->getTags());
    }

    public function testMergeTagsSkipsSourceEqualToTarget(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(1);
        $a->setTags(['todo']);

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$a]);

        $affected = $this->markdownNoteManager->mergeTags($user, ['todo'], 'todo');

        self::assertSame(0, $affected, 'no-op when only source equals target');
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

        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$a, $b, $c]);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $affected = $this->markdownNoteManager->removeTag($user, 'gone');

        self::assertSame(2, $affected);
        self::assertSame(['kept'], $a->getTags());
        self::assertSame(['kept'], $b->getTags());
        self::assertSame([], $c->getTags());
    }

    public function testRemoveTagNoopWhenAbsent(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(1);
        $a->setTags(['x']);
        $this->markdownNoteRepository->method('findAllWithContentForUser')->willReturn([$a]);
        $this->entityManager->expects(self::never())->method('flush');

        self::assertSame(0, $this->markdownNoteManager->removeTag($user, 'missing'));
    }

    public function testUpdateRemovesImagesNoLongerReferenced(): void
    {
        $user = $this->makeUser(99);
        $note = new MarkdownNote();
        $note->setUser($user);
        $note->setTitle('with images');
        $note->setContent('![a](/backend/notes/markdown/images/keep.png) and ![b](/backend/notes/markdown/images/drop.png)');

        $this->prepareImageFile($user, 'keep.png');
        $this->prepareImageFile($user, 'drop.png');

        $newContent = '![a](/backend/notes/markdown/images/keep.png) only';
        $input = new MarkdownNoteInput(title: 'with images', content: $newContent);

        $this->markdownNoteManager->update($note, $input);

        self::assertFileExists($this->imageDir.'/99/keep.png');
        self::assertFileDoesNotExist($this->imageDir.'/99/drop.png');
    }

    public function testDeleteRemovesEveryReferencedImage(): void
    {
        $user = $this->makeUser(99);
        $note = new MarkdownNote();
        $note->setUser($user);
        $note->setContent('![a](/backend/notes/markdown/images/one.png) ![b](/backend/notes/markdown/images/two.png)');

        $this->prepareImageFile($user, 'one.png');
        $this->prepareImageFile($user, 'two.png');

        $this->markdownNoteManager->delete($note);

        self::assertFileDoesNotExist($this->imageDir.'/99/one.png');
        self::assertFileDoesNotExist($this->imageDir.'/99/two.png');
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
