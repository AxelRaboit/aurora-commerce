<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Search;

use Aurora\Core\Search\SearchProviderInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Project\Repository\ProjectRepository;

use function sprintf;

final readonly class ProjectSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {}

    public function search(string $query, int $limit, CoreUserInterface $user): array
    {
        $lines = [];
        foreach ($this->projectRepository->searchByTitle($query) as $project) {
            $lines[] = sprintf(
                '[PROJECT #%d] (status=%s, ref=%s) %s',
                $project->getId(),
                $project->getStatus()->value,
                (string) $project->getReference(),
                (string) $project->getTitle(),
            );
        }

        return $lines;
    }
}
