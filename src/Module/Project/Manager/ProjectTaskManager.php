<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Notification\Manager\NotificationManager;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Project\Dto\ProjectTaskInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Aurora\Module\Project\Repository\ProjectLabelRepository;
use Aurora\Module\Project\Repository\ProjectSprintRepository;
use Aurora\Module\Project\Repository\ProjectTaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProjectTaskManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private AuditLogger $auditLogger,
        private SequenceGenerator $sequenceGenerator,
        private ProjectTaskRepository $projectTaskRepository,
        private ProjectColumnRepository $columnRepository,
        private ProjectLabelRepository $labelRepository,
        private ProjectSprintRepository $sprintRepository,
        private NotificationManager $notifier,
        private TranslatorInterface $translator,
    ) {}

    public function create(Project $project, ProjectTaskInput $input): ProjectTask
    {
        $task = new ProjectTask();
        $task->setProject($project);
        $this->applyInput($task, $input);
        $task->setReference($this->sequenceGenerator->next(SequencePrefixEnum::ProjectTask->value));
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.created', 'ProjectTask', $task->getId(), [
            'projectId' => $project->getId(),
            'title' => $task->getTitle(),
            'reference' => $task->getReference(),
        ]);

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

    public function update(ProjectTask $task, ProjectTaskInput $input): void
    {
        $previousAssigneeId = $task->getAssignee()?->getId();
        $this->applyInput($task, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.updated', 'ProjectTask', $task->getId(), [
            'projectId' => $task->getProject()->getId(),
            'title' => $task->getTitle(),
        ]);

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

    public function delete(ProjectTask $task): void
    {
        $projectId = $task->getProject()->getId();
        $title = $task->getTitle();
        $id = $task->getId();

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.deleted', 'ProjectTask', $id, [
            'projectId' => $projectId,
            'title' => $title,
        ]);
    }

    /** @param list<int> $orderedIds */
    public function reorder(Project $project, array $orderedIds, ?ProjectColumn $targetColumn = null): void
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
                if ($targetColumn instanceof ProjectColumn) {
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

    private function applyInput(ProjectTask $task, ProjectTaskInput $input): void
    {
        $task->setTitle($input->title);
        $task->setDescription($input->description);
        if (null !== $input->columnId) {
            $column = $this->columnRepository->find($input->columnId);
            if (null !== $column) {
                $task->setColumn($column);
            }
        }

        $task->setPriority($input->priorityEnum());
        $task->setAssignee($input->assigneeId ? $this->userRepository->find($input->assigneeId) : null);
        $task->setDueDate($input->dueDate ? new DateTimeImmutable($input->dueDate) : null);
        $task->setPosition($input->position);
        $task->setStoryPoints($input->storyPoints);
        $task->setEstimateMinutes($input->estimateMinutes);

        // Sync labels (many-to-many) in a single batch query.
        $desiredLabels = [];
        if ([] !== $input->labelIds) {
            foreach ($this->labelRepository->findBy(['id' => $input->labelIds]) as $label) {
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
        if ([] !== $input->watcherIds) {
            foreach ($this->userRepository->findBy(['id' => $input->watcherIds]) as $watcher) {
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
        $task->setSprint(null !== $input->sprintId ? $this->sprintRepository->find($input->sprintId) : null);
    }
}
