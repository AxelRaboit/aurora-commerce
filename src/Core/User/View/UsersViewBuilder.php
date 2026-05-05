<?php

declare(strict_types=1);

namespace Aurora\Core\User\View;

use Aurora\Core\Module\PermissionRegistry;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;

final readonly class UsersViewBuilder
{
    public function __construct(private PermissionRegistry $permissionRegistry) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(bool $isDev, ?User $currentUser): array
    {
        $selectableRoles = $isDev
            ? [UserRoleEnum::Dev, ...UserRoleEnum::selectableForAdmin()]
            : UserRoleEnum::selectableForAdmin();

        $roles = array_map(
            static fn (UserRoleEnum $role): array => ['value' => $role->value, 'label' => $role->label()],
            $selectableRoles,
        );

        $currentUserPriority = $currentUser instanceof User
            ? UserRoleEnum::highestPriorityForRoles($currentUser->getRoles())
            : 0;

        // All privileges grouped by module — used by the Dev UI to assign privileges per user.
        $privilegesByModule = [];
        foreach ($this->permissionRegistry->byModule() as $moduleId => $privileges) {
            if ([] !== $privileges) {
                $privilegesByModule[] = [
                    'module' => $moduleId,
                    'privileges' => $privileges,
                ];
            }
        }

        return [
            'roles' => $roles,
            'isDev' => $isDev,
            'currentUserPriority' => $currentUserPriority,
            'privilegesByModule' => $privilegesByModule,
        ];
    }
}
