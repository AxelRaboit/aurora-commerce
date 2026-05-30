<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Search;

use Aurora\Core\Search\BackendSearchProviderInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Aurora\Module\Project\Repository\ProjectRepository;
use Aurora\Module\Project\Repository\ProjectTaskRepository;

/**
 * Project slice of the backend global search: projects and tasks (by title).
 * Lives in the Project module so the General search controller never imports
 * Project repositories.
 */
final readonly class ProjectBackendSearchProvider implements BackendSearchProviderInterface
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private ProjectTaskRepository $taskRepository,
    ) {}

    public function search(string $query): array
    {
        $projectsSerialized = array_map(
            static fn (ProjectInterface $project): array => [
                'id' => $project->getId(),
                'title' => $project->getTitle(),
                'status' => $project->getStatus()->value,
                'reference' => $project->getReference(),
            ],
            $this->projectRepository->searchByTitle($query),
        );

        $tasksSerialized = array_map(
            static fn (ProjectTaskInterface $task): array => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'reference' => $task->getReference(),
                'projectId' => $task->getProject()->getId(),
                'projectTitle' => $task->getProject()->getTitle(),
            ],
            $this->taskRepository->searchByTitle($query),
        );

        return [
            'projects' => $projectsSerialized,
            'tasks' => $tasksSerialized,
        ];
    }
}
