<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Project\Dto\ProjectColumnInputInterface;
use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Aurora\Module\Project\Entity\ProjectInterface;

interface ProjectColumnManagerInterface
{
    /**
     * Seed the 3 default Kanban columns when a project is created.
     *
     * @return list<ProjectColumnInterface>
     */
    public function seedDefaults(ProjectInterface $project): array;

    public function create(ProjectInterface $project, ProjectColumnInputInterface $input): ProjectColumnInterface;

    public function update(ProjectColumnInterface $column, ProjectColumnInputInterface $input): void;

    public function delete(ProjectColumnInterface $column): void;

    /** @param list<int> $orderedIds */
    public function reorder(ProjectInterface $project, array $orderedIds): void;
}
