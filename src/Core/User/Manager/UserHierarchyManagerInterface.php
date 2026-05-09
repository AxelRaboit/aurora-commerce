<?php

declare(strict_types=1);

namespace Aurora\Core\User\Manager;

use Aurora\Core\User\Entity\User;

interface UserHierarchyManagerInterface
{
    public function setManager(User $user, ?int $managerId): void;

    public function applyManager(User $user, ?int $managerId): void;
}
