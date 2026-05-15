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

    public function testReorderAssignsPositionByIndex(): void
    {
        $user = $this->makeUser();
        $a = $this->makeNoteWithId(10);
        $b = $this->makeNoteWithId(20);
        $c = $this->makeNoteWithId(30);

        $this->repo->method('findBy')->willReturn([$a, $b, $c]);
        $this->em->expects(self::once())->method('flush');

        $this->manager->reorder($user, [30, 10, 20]);

        self::assertSame(0, $c->getPosition());
        self::assertSame(1, $a->getPosition());
        self::assertSame(2, $b->getPosition());
    }

    public function testReorderOnEmptyListIsNoop(): void
    {
        $this->repo->expects(self::never())->method('findBy');
        $this->em->expects(self::never())->method('flush');

        $this->manager->reorder($this->makeUser(), []);
    }

    private function makeNoteWithId(int $id): MarkdownNoteInterface
    {
        $note = new MarkdownNote();
        $r = new ReflectionProperty(MarkdownNote::class, 'id');
        $r->setValue($note, $id);

        return $note;
    }
}
