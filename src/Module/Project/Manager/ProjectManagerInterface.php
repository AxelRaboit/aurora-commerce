<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Project\Dto\ProjectInputInterface;
use Aurora\Module\Project\Entity\ProjectInterface;

interface ProjectManagerInterface
{
    public function create(ProjectInputInterface $input): ProjectInterface;

    public function update(ProjectInterface $project, ProjectInputInterface $input): void;

    public function delete(ProjectInterface $project): void;
}
