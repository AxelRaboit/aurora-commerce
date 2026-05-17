<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectTaskTimeEntryInputInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntryInterface;

interface ProjectTaskTimeEntryManagerInterface
{
    public function create(ProjectTaskInterface $task, User $user, ProjectTaskTimeEntryInputInterface $input): ProjectTaskTimeEntryInterface;

    public function delete(ProjectTaskTimeEntryInterface $entry): void;
}
