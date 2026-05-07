<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\DTO\ProjectTaskTimeEntryInput;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectTaskTimeEntryManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
    ) {}

    public function create(ProjectTask $task, User $user, ProjectTaskTimeEntryInput $input): ProjectTaskTimeEntry
    {
        $entry = new ProjectTaskTimeEntry();
        $entry->setTask($task)
            ->setUser($user)
            ->setMinutes($input->minutes)
            ->setNote($input->note)
            ->setLoggedAt(new DateTimeImmutable(null !== $input->loggedAt && '' !== $input->loggedAt ? $input->loggedAt : 'now'));
        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.time.logged', 'ProjectTask', $task->getId(), [
            'projectId' => $task->getProject()->getId(),
            'minutes' => $input->minutes,
            'entryId' => $entry->getId(),
        ]);

        return $entry;
    }

    public function delete(ProjectTaskTimeEntry $entry): void
    {
        $taskId = $entry->getTask()->getId();
        $minutes = $entry->getMinutes();
        $entryId = $entry->getId();

        $this->entityManager->remove($entry);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.time.deleted', 'ProjectTask', $taskId, [
            'projectId' => $entry->getTask()->getProject()->getId(),
            'minutes' => $minutes,
            'entryId' => $entryId,
        ]);
    }
}
