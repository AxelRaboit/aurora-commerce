<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Project\Dto\ProjectTaskInputInterface;
use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;

interface ProjectTaskManagerInterface
{
    public function create(ProjectInterface $project, ProjectTaskInputInterface $input): ProjectTaskInterface;

    public function update(ProjectTaskInterface $task, ProjectTaskInputInterface $input): void;

    public function delete(ProjectTaskInterface $task): void;

    /** @param list<int> $orderedIds */
    public function reorder(ProjectInterface $project, array $orderedIds, ?ProjectColumnInterface $targetColumn = null): void;
}
