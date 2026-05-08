<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Project\Dto\ProjectTaskItemsInputInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;

interface ProjectTaskItemManagerInterface
{
    public function replaceForTask(ProjectTaskInterface $task, ProjectTaskItemsInputInterface $input): void;
}
