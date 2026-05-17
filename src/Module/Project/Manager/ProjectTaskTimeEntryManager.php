<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectTaskTimeEntryInputInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntryInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskTimeEntryManagerInterface::class)]
class ProjectTaskTimeEntryManager implements ProjectTaskTimeEntryManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(ProjectTaskInterface $task, User $user, ProjectTaskTimeEntryInputInterface $input): ProjectTaskTimeEntryInterface
    {
        $entry = $this->createProjectTaskTimeEntry();
        $entry->setTask($task)
            ->setUser($user)
            ->setMinutes($input->getMinutes())
            ->setNote($input->getNote())
            ->setLoggedAt(new DateTimeImmutable(null !== $input->getLoggedAt() && '' !== $input->getLoggedAt() ? $input->getLoggedAt() : 'now'));
        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.time.logged', 'ProjectTask', $task->getId(), [
            'projectId' => $task->getProject()->getId(),
            'minutes' => $input->getMinutes(),
            'entryId' => $entry->getId(),
        ]);

        return $entry;
    }

    public function delete(ProjectTaskTimeEntryInterface $entry): void
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

    protected function createProjectTaskTimeEntry(): ProjectTaskTimeEntryInterface
    {
        return new ProjectTaskTimeEntry();
    }
}
