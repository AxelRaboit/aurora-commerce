<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectTaskTimeEntryInputInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use Aurora\Module\Project\Manager\ProjectTaskTimeEntryManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProjectTaskTimeEntryManagerTest extends TestCase
{
    private function makeInput(int $minutes, ?string $note = null, ?string $loggedAt = null): ProjectTaskTimeEntryInputInterface
    {
        $input = $this->createStub(ProjectTaskTimeEntryInputInterface::class);
        $input->method('getMinutes')->willReturn($minutes);
        $input->method('getNote')->willReturn($note);
        $input->method('getLoggedAt')->willReturn($loggedAt);

        return $input;
    }

    public function testCreatePersistsAndAudits(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::once())
            ->method('log')
            ->with('project', 'task.time.logged', 'ProjectTask', self::anything(), self::anything());

        $task = (new ProjectTask())->setProject(new Project());
        $user = new User();

        $manager = new ProjectTaskTimeEntryManager($em, $audit);
        $entry = $manager->create($task, $user, $this->makeInput(45, 'Work', '2026-01-15'));

        self::assertInstanceOf(ProjectTaskTimeEntry::class, $entry);
        self::assertSame($task, $entry->getTask());
        self::assertSame($user, $entry->getUser());
        self::assertSame(45, $entry->getMinutes());
        self::assertSame('Work', $entry->getNote());
    }

    public function testCreateUsesNowWhenNoLoggedAt(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $audit = $this->createStub(AuditLogger::class);

        $task = (new ProjectTask())->setProject(new Project());

        $manager = new ProjectTaskTimeEntryManager($em, $audit);
        $entry = $manager->create($task, new User(), $this->makeInput(30));

        self::assertNotNull($entry->getLoggedAt());
    }

    public function testDeleteRemovesAndAudits(): void
    {
        $task = (new ProjectTask())->setProject(new Project());
        $entry = (new ProjectTaskTimeEntry())->setTask($task)->setMinutes(60);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($entry);
        $em->expects(self::once())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::once())
            ->method('log')
            ->with('project', 'task.time.deleted', 'ProjectTask', self::anything(), self::anything());

        (new ProjectTaskTimeEntryManager($em, $audit))->delete($entry);
    }
}
