<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Notification\Manager\NotificationManager;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Project\Dto\ProjectTaskInputInterface;
use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Aurora\Module\Project\Repository\ProjectLabelRepository;
use Aurora\Module\Project\Repository\ProjectSprintRepository;
use Aurora\Module\Project\Repository\ProjectTaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ProjectTaskManagerInterface::class)]
class ProjectTaskManager implements ProjectTaskManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UserRepository $userRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly ProjectTaskRepository $projectTaskRepository,
        protected readonly ProjectColumnRepository $columnRepository,
        protected readonly ProjectLabelRepository $labelRepository,
        protected readonly ProjectSprintRepository $sprintRepository,
        protected readonly NotificationManager $notifier,
        protected readonly TranslatorInterface $translator,
    ) {}

    public function create(ProjectInterface $project, ProjectTaskInputInterface $input): ProjectTaskInterface
    {
        $task = $this->createProjectTask();
        $task->setProject($project);
        $this->applyInput($task, $input);
        $task->setReference($this->sequenceGenerator->next(SequencePrefixEnum::ProjectTask->value));
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->auditCreated($task);

        // Notify newly assigned user.
        if ($task->getAssignee() instanceof User) {
            $assignee = $task->getAssignee();
            $this->notifier->notify(
                $assignee,
                'project.task.assigned',
                $task->getTitle(),
                $this->translator->trans('backend.notifications.taskAssigned', [], null, $assignee->getLocale()->value),
                null,
                ['projectId' => $project->getId(), 'taskId' => $task->getId()],
            );
        }

        return $task;
    }

    public function update(ProjectTaskInterface $task, ProjectTaskInputInterface $input): void
    {
        $previousAssigneeId = $task->getAssignee()?->getId();
        $this->applyInput($task, $input);
        $this->entityManager->flush();

        $this->auditUpdated($task);

        // Notify if assignee changed and is now set.
        $newAssignee = $task->getAssignee();
        if ($newAssignee instanceof User && $newAssignee->getId() !== $previousAssigneeId) {
            $this->notifier->notify(
                $newAssignee,
                'project.task.assigned',
                $task->getTitle(),
                $this->translator->trans('backend.notifications.taskAssigned', [], null, $newAssignee->getLocale()->value),
                null,
                ['projectId' => $task->getProject()->getId(), 'taskId' => $task->getId()],
            );
        }
    }

    public function delete(ProjectTaskInterface $task): void
    {
        $this->auditDeleted($task);

        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    /** @param list<int> $orderedIds */
    public function reorder(ProjectInterface $project, array $orderedIds, ?ProjectColumnInterface $targetColumn = null): void
    {
        $tasks = $this->projectTaskRepository->findByProject($project);
        $indexed = [];
        foreach ($tasks as $task) {
            $indexed[$task->getId()] = $task;
        }

        $movedIds = [];
        foreach ($orderedIds as $position => $taskId) {
            if (isset($indexed[$taskId])) {
                $indexed[$taskId]->setPosition($position);
                if ($targetColumn instanceof ProjectColumnInterface) {
                    $indexed[$taskId]->setColumn($targetColumn);
                }

                $movedIds[] = $taskId;
            }
        }

        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.reordered', 'Project', $project->getId(), [
            'projectId' => $project->getId(),
            'targetColumnId' => $targetColumn?->getId(),
            'taskIds' => $movedIds,
        ]);
    }

    protected function createProjectTask(): ProjectTaskInterface
    {
        return new ProjectTask();
    }

    protected function applyInput(ProjectTaskInterface $task, ProjectTaskInputInterface $input): void
    {
        $task->setTitle($input->getTitle());
        $task->setDescription($input->getDescription());
        if (null !== $input->getColumnId()) {
            $column = $this->columnRepository->find($input->getColumnId());
            if (null !== $column) {
                $task->setColumn($column);
            }
        }

        $task->setPriority($input->getPriorityEnum());
        $task->setAssignee(null !== $input->getAssigneeId() ? $this->userRepository->find($input->getAssigneeId()) : null);
        $task->setDueDate($input->getDueDate() ? new DateTimeImmutable($input->getDueDate()) : null);
        $task->setPosition($input->getPosition());
        $task->setStoryPoints($input->getStoryPoints());
        $task->setEstimateMinutes($input->getEstimateMinutes());

        // Sync labels (many-to-many) in a single batch query.
        $desiredLabels = [];
        if ([] !== $input->getLabelIds()) {
            foreach ($this->labelRepository->findBy(['id' => $input->getLabelIds()]) as $label) {
                $desiredLabels[(int) $label->getId()] = $label;
            }
        }

        foreach ($task->getLabels()->toArray() as $existing) {
            if (!isset($desiredLabels[(int) $existing->getId()])) {
                $task->removeLabel($existing);
            }
        }

        foreach ($desiredLabels as $label) {
            $task->addLabel($label);
        }

        // Sync watchers (many-to-many).
        $desiredWatchers = [];
        if ([] !== $input->getWatcherIds()) {
            foreach ($this->userRepository->findBy(['id' => $input->getWatcherIds()]) as $watcher) {
                $desiredWatchers[(int) $watcher->getId()] = $watcher;
            }
        }

        foreach ($task->getWatchers()->toArray() as $existing) {
            if (!isset($desiredWatchers[(int) $existing->getId()])) {
                $task->removeWatcher($existing);
            }
        }

        foreach ($desiredWatchers as $watcher) {
            $task->addWatcher($watcher);
        }

        // Sprint (single FK).
        $task->setSprint(null !== $input->getSprintId() ? $this->sprintRepository->find($input->getSprintId()) : null);
    }

    protected function auditCreated(ProjectTaskInterface $task): void
    {
        $this->auditLogger->log('project', 'task.created', 'ProjectTask', $task->getId(), [
            ...$this->auditPayload($task),
            'projectId' => $task->getProject()->getId(),
        ]);
    }

    protected function auditUpdated(ProjectTaskInterface $task): void
    {
        $this->auditLogger->log('project', 'task.updated', 'ProjectTask', $task->getId(), [
            ...$this->auditPayload($task),
            'projectId' => $task->getProject()->getId(),
        ]);
    }

    protected function auditDeleted(ProjectTaskInterface $task): void
    {
        $this->auditLogger->log('project', 'task.deleted', 'ProjectTask', $task->getId(), [
            ...$this->auditPayload($task),
            'projectId' => $task->getProject()->getId(),
        ]);
    }

    protected function auditPayload(ProjectTaskInterface $task): array
    {
        return ['title' => $task->getTitle(), 'reference' => $task->getReference()];
    }
}
