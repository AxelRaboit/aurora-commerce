<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Project\Dto\ProjectLabelInputInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectLabelInterface;

interface ProjectLabelManagerInterface
{
    public function create(ProjectInterface $project, ProjectLabelInputInterface $input): ProjectLabelInterface;

    public function update(ProjectLabelInterface $label, ProjectLabelInputInterface $input): void;

    public function delete(ProjectLabelInterface $label): void;
}
