<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectSavedViewInterface;

interface ProjectSavedViewManagerInterface
{
    /** @param array<string, mixed> $filters */
    public function create(User $owner, ProjectInterface $project, string $name, array $filters): ProjectSavedViewInterface;

    /** @param array<string, mixed> $filters */
    public function update(ProjectSavedViewInterface $view, string $name, array $filters): void;

    public function delete(ProjectSavedViewInterface $view): void;
}
