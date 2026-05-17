<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\User\Manager;

use Aurora\Core\Platform\User\Entity\User;

interface UserHierarchyManagerInterface
{
    public function setManager(User $user, ?int $managerId): void;

    public function applyManager(User $user, ?int $managerId): void;
}
