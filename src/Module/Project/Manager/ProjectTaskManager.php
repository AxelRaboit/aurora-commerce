<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Project\DTO\ProjectTaskInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Aurora\Module\Project\Repository\ProjectTaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectTaskManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private AuditLogger $auditLogger,
        private SequenceGenerator $sequenceGenerator,
        private ProjectTaskRepository $projectTaskRepository,
        private ProjectColumnRepository $columnRepository,
    ) {}

    public function create(Project $project, ProjectTaskInput $input): ProjectTask
    {
        $task = new ProjectTask();
        $task->setProject($project);
        $this->applyInput($task, $input);
        $task->setReference($this->sequenceGenerator->next(SequencePrefixEnum::ProjectTask->value));
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.created', 'ProjectTask', $task->getId(), ['title' => $task->getTitle(), 'reference' => $task->getReference()]);

        return $task;
    }

    public function update(ProjectTask $task, ProjectTaskInput $input): void
    {
        $this->applyInput($task, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.updated', 'ProjectTask', $task->getId(), ['title' => $task->getTitle()]);
    }

    public function delete(ProjectTask $task): void
    {
        $title = $task->getTitle();
        $id = $task->getId();

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.deleted', 'ProjectTask', $id, ['title' => $title]);
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
    }
}
