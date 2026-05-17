<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectTaskCommentInputInterface;
use Aurora\Module\Project\Entity\ProjectTaskCommentInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;

interface ProjectTaskCommentManagerInterface
{
    public function create(ProjectTaskInterface $task, User $author, ProjectTaskCommentInputInterface $input): ProjectTaskCommentInterface;

    public function delete(ProjectTaskCommentInterface $comment): void;
}
