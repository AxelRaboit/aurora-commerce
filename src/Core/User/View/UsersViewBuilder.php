<?php

declare(strict_types=1);

namespace Aurora\Core\User\View;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;

/**
 * Builds the Twig payload for the admin users page. Centralises the role
 * options + current-user priority shape so the controller stays focused on
 * JSON CRUD operations.
 */
final readonly class UsersViewBuilder
{
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

        return [
            'roles' => $roles,
            'isDev' => $isDev,
            'currentUserPriority' => $currentUserPriority,
        ];
    }
}
