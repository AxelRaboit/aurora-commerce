<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Search;

use Aurora\Core\General\Search\Provider\SearchProviderInterface;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Project\Repository\ProjectTaskRepository;

use function sprintf;

final readonly class ProjectTaskSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private ProjectTaskRepository $taskRepository,
    ) {}

    public function search(string $query, int $limit, CoreUserInterface $user): array
    {
        $lines = [];
        foreach ($this->taskRepository->searchByTitle($query) as $task) {
            $lines[] = sprintf(
                '[TASK #%d] (ref=%s, project=%s) %s',
                $task->getId(),
                (string) $task->getReference(),
                (string) $task->getProject()->getTitle(),
                (string) $task->getTitle(),
            );
        }

        return $lines;
    }
}
