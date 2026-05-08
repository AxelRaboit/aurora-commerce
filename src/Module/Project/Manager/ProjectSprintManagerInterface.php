<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Project\Dto\ProjectSprintInputInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectSprintInterface;

interface ProjectSprintManagerInterface
{
    public function create(ProjectInterface $project, ProjectSprintInputInterface $input): ProjectSprintInterface;

    public function update(ProjectSprintInterface $sprint, ProjectSprintInputInterface $input): void;

    public function delete(ProjectSprintInterface $sprint): void;
}
