<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\PostIt\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Notes\PostIt\Dto\PostItNoteInput;
use Aurora\Module\Notes\PostIt\Entity\PostItNote;
use Aurora\Module\Notes\PostIt\Manager\PostItNoteManager;
use Aurora\Module\Platform\User\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class PostItNoteManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PostItNoteManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->manager = new PostItNoteManager($this->entityManager, $auditLogger);
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $r = new ReflectionProperty(User::class, 'id');
        $r->setValue($user, $id);

        return $user;
    }

    public function testCreateAssignsUserAgencyAndPersists(): void
    {
        $user = $this->makeUser();
        $input = new PostItNoteInput(title: 'Hello', content: 'world', color: '#FFEB3B', positionX: 10, positionY: 20);

        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $note = $this->manager->create($user, $input);

        self::assertSame($user, $note->getUser());
        self::assertSame('Hello', $note->getTitle());
        self::assertSame('world', $note->getContent());
        self::assertSame('#FFEB3B', $note->getColor());
        self::assertSame(10, $note->getPositionX());
        self::assertSame(20, $note->getPositionY());
        // Width/height aren't in the DTO — Entity defaults must apply.
        self::assertSame(220, $note->getWidth());
        self::assertSame(220, $note->getHeight());
    }

    public function testCreateKeepsEntityColorWhenInputColorIsNull(): void
    {
        // Partial-update semantics: if the DTO doesn't carry a color, the
        // existing (or default) value on the entity must stay untouched.
        $note = $this->manager->create($this->makeUser(), new PostItNoteInput());

        self::assertSame('#FFEB3B', $note->getColor());
    }

    public function testUpdateAppliesAllProvidedFields(): void
    {
        $note = new PostItNote();
        $note->setUser($this->makeUser());

        // `flush` is called once for the note update + once by the audit
        // logger when it persists its own row, so `atLeastOnce()` is the
        // correct constraint here — same convention as MarkdownNoteManagerTest.
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($note, new PostItNoteInput(
            title: 'New title',
            content: 'New body',
            color: '#A5D6A7',
            positionX: 99,
            positionY: 88,
        ));

        self::assertSame('New title', $note->getTitle());
        self::assertSame('New body', $note->getContent());
        self::assertSame('#A5D6A7', $note->getColor());
        self::assertSame(99, $note->getPositionX());
        self::assertSame(88, $note->getPositionY());
    }

    public function testUpdateLeavesOptionalFieldsUntouchedWhenNull(): void
    {
        $note = new PostItNote();
        $note->setUser($this->makeUser());
        $note->setColor('#90CAF9');
        $note->setPositionX(50);
        $note->setPositionY(60);

        $this->manager->update($note, new PostItNoteInput(title: 'Only title'));

        self::assertSame('Only title', $note->getTitle());
        // Color/positions remain as-is when DTO carries null — the assignment
        // is gated by the `if (null !== ...)` checks in applyInput().
        self::assertSame('#90CAF9', $note->getColor());
        self::assertSame(50, $note->getPositionX());
        self::assertSame(60, $note->getPositionY());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $note = new PostItNote();
        $note->setUser($this->makeUser());

        $this->entityManager->expects(self::once())->method('remove')->with($note);
        // `flush` runs at least twice: once for the delete, once by the
        // audit log row insertion — same pattern as Markdown's test.
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($note);
    }

    public function testMoveUpdatesPositionAndFlushes(): void
    {
        $note = new PostItNote();
        $note->setUser($this->makeUser());
        $note->setColor('#FFEB3B');
        $note->setTitle('keep');

        // No audit log on `move` (lightweight position-only update), so
        // a single flush is the correct expectation here.
        $this->entityManager->expects(self::once())->method('flush');

        $this->manager->move($note, 300, 150);

        self::assertSame(300, $note->getPositionX());
        self::assertSame(150, $note->getPositionY());
        // Move must NOT touch the encrypted title/content — verifying via
        // the title field which remains intact.
        self::assertSame('keep', $note->getTitle());
    }

    public function testResizeClampsBelowMinimum(): void
    {
        $note = new PostItNote();
        $note->setUser($this->makeUser());

        // No audit log on resize either — same rationale as `move`.
        $this->entityManager->expects(self::once())->method('flush');

        // Manager enforces min 120×80 — anything smaller is bumped up so the
        // post-it can't collapse to invisibility.
        $this->manager->resize($note, 50, 30);

        self::assertSame(120, $note->getWidth());
        self::assertSame(80, $note->getHeight());
    }

    public function testResizeAcceptsLargerDimensions(): void
    {
        $note = new PostItNote();
        $note->setUser($this->makeUser());

        $this->manager->resize($note, 400, 350);

        self::assertSame(400, $note->getWidth());
        self::assertSame(350, $note->getHeight());
    }
}
