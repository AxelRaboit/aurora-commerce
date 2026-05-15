<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Markdown\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Notes\Markdown\Dto\MarkdownNoteInput;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Notes\Markdown\Manager\MarkdownNoteManager;
use Aurora\Module\Notes\Markdown\Repository\MarkdownNoteRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class MarkdownNoteManagerTest extends TestCase
{
    private EntityManagerInterface $em;
    private MarkdownNoteRepository $repo;
    private MarkdownNoteManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(MarkdownNoteRepository::class);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $this->manager = new MarkdownNoteManager(
            $this->em,
            $this->repo,
            new AuditLogger($this->em, $security, new SequenceGenerator($this->createStub(Connection::class)), $this->createStub(SettingRepository::class)),
        );
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

        $this->repo->method('findMaxPositionForUserAndParent')->willReturn(null);
        $this->em->expects(self::atLeastOnce())->method('persist');
        $this->em->expects(self::atLeastOnce())->method('flush');

        $note = $this->manager->create($user, $input);

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

        $this->repo->method('findMaxPositionForUserAndParent')->willReturn(4);

        $note = $this->manager->create($user, $input);

        self::assertSame(5, $note->getPosition());
    }

    public function testCreateRespectsExplicitPosition(): void
    {
        $user = $this->makeUser();
        $input = new MarkdownNoteInput(title: 'Pinned', position: 99);

        $note = $this->manager->create($user, $input);

        self::assertSame(99, $note->getPosition());
    }

    public function testUpdateAppliesInputAndFlushes(): void
    {
        $user = $this->makeUser();
        $note = new MarkdownNote();
        $note->setUser($user);
        $note->setTitle('old');

        $input = new MarkdownNoteInput(title: 'new', content: 'updated body', tags: ['a', 'b']);

        $this->em->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($note, $input);

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

        $this->repo->expects(self::once())->method('findOneByUserAndId')->with($user, 42)->willReturn($parent);

        $input = new MarkdownNoteInput(parentId: 42);
        $this->manager->update($note, $input);

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
        $this->manager->update($note, $input);

        self::assertNull($note->getParent());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $note = new MarkdownNote();
        $note->setUser($this->makeUser());

        $this->em->expects(self::once())->method('remove')->with($note);
        $this->em->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($note);
    }

    public function testMoveUpdatesParent(): void
    {
        $note = new MarkdownNote();
        $parent = new MarkdownNote();

        $this->em->expects(self::once())->method('flush');

        $this->manager->move($note, $parent);

        self::assertSame($parent, $note->getParent());
    }

    public function testReorderAssignsParentAndPositionFromEntries(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(10);
        $b = $this->makeNoteWithId(20);
        $c = $this->makeNoteWithId(30);

        $this->repo->method('findBy')->willReturn([$a, $b, $c]);
        $this->em->expects(self::once())->method('flush');

        // 10 stays root pos 0; 20 becomes child of 10 at pos 0; 30 stays root pos 1
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

        $this->repo->method('findBy')->willReturn([$a, $b]);

        $this->expectException(InvalidArgumentException::class);

        // 10's parent is 20, 20's parent is 10 → cycle
        $this->manager->reorder($user, [
            ['id' => 10, 'parentId' => 20, 'position' => 0],
            ['id' => 20, 'parentId' => 10, 'position' => 0],
        ]);
    }

    public function testReorderOnEmptyListIsNoop(): void
    {
        $this->repo->expects(self::never())->method('findBy');
        $this->em->expects(self::never())->method('flush');

        $this->manager->reorder($this->makeUser(), []);
    }

    public function testUpdateRenamesWikiLinksInOtherNotesWhenTitleChanges(): void
    {
        $user = $this->makeUser();
        $note = $this->makeNoteWithId(1);
        $note->setUser($user);
        $note->setTitle('Old Title');

        $other = $this->makeNoteWithId(2);
        $other->setContent('See [[Old Title]] for details.');

        $this->repo->expects(self::once())->method('findAllWithContentForUser')->with($user)->willReturn([$note, $other]);

        $this->manager->update($note, new MarkdownNoteInput(title: 'New Title'));

        self::assertSame('See [[New Title]] for details.', $other->getContent());
    }

    public function testUpdateDoesNotRenameWhenOldTitleWasNull(): void
    {
        $user = $this->makeUser();
        $note = $this->makeNoteWithId(1);
        $note->setUser($user);
        $note->setTitle(null);

        $this->repo->expects(self::never())->method('findAllWithContentForUser');

        $this->manager->update($note, new MarkdownNoteInput(title: 'Brand new'));
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

        $this->repo->method('findAllWithContentForUser')->willReturn([$target, $linker, $unrelated]);

        $results = $this->manager->backlinks($user, $target);

        self::assertCount(1, $results);
        self::assertSame(11, $results[0]['id']);
        self::assertSame('A', $results[0]['title']);
    }

    public function testBacklinksReturnsEmptyForUntitledNote(): void
    {
        $user = $this->makeUser();
        $target = $this->makeNoteWithId(10);
        $target->setTitle(null);

        $this->repo->expects(self::never())->method('findAllWithContentForUser');

        self::assertSame([], $this->manager->backlinks($user, $target));
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

        $this->repo->method('findAllWithContentForUser')->willReturn([$target, $linker, $mentioner]);

        $results = $this->manager->unlinkedMentions($user, $target);

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

        $this->repo->method('findAllWithContentForUser')->willReturn([$a, $b, $c]);

        $graph = $this->manager->graph($user);

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

        $this->repo->method('findAllWithContentForUser')->willReturn([$a]);

        $graph = $this->manager->graph($user);

        self::assertSame([], $graph['edges']);
    }

    private function makeNoteWithId(int $id): MarkdownNoteInterface
    {
        $note = new MarkdownNote();
        $r = new ReflectionProperty(MarkdownNote::class, 'id');
        $r->setValue($note, $id);

        return $note;
    }
}
