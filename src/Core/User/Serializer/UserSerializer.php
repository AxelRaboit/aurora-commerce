<?php

declare(strict_types=1);

namespace Aurora\Core\User\Serializer;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;

use const DATE_ATOM;

final readonly class UserSerializer
{
    /**
     * Lightweight payload used in paginated lists. Does NOT load the
     * subordinates collection — use {@see serializeWithSubordinates()} for
     * single-user views (e.g. profile detail modal).
     */
    public function serialize(User $user): array
    {
        // Visible role for the badge. Dev is hidden (shown via isDev flag). ROLE_USER is only
        // shown when it is the user's actual highest role — it is excluded when Admin or Dev is
        // present because getRoles() always appends ROLE_USER via the entity getter.
        $effectivePriority = UserRoleEnum::highestPriorityForRoles($user->getRoles());
        $primaryRole = match (true) {
            $effectivePriority >= UserRoleEnum::Dev->priority() => null,
            $effectivePriority >= UserRoleEnum::Admin->priority() => UserRoleEnum::Admin,
            default => UserRoleEnum::User,
        };

        // Effective priority used by the frontend `canActOn` guard. MUST consider the Dev role,
        // otherwise a Dev user serialises as priority 0 and any admin appears able to edit them.

        $manager = $user->getManager();

        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'role' => $primaryRole?->value,
            'roleLabel' => $primaryRole?->label(),
            'rolePriority' => $effectivePriority,
            'isDev' => in_array(UserRoleEnum::Dev->value, $user->getRoles(), true),
            'type' => $user->getType()->value,
            'typeLabel' => $user->getType()->label(),
            'status' => $user->getStatus()->value,
            'statusLabel' => $user->getStatus()->label(),
            'locale' => $user->getLocale()->value,
            'profilePhotoUrl' => $user->getProfilePhotoUrl(),
            'moodMessage' => $user->getMoodMessage(),
            'moodMessageMaxLength' => User::MOOD_MESSAGE_MAX_LENGTH,
            'managerId' => $manager?->getId(),
            'manager' => $manager instanceof User ? ['id' => $manager->getId(), 'name' => $manager->getName()] : null,
            'invitedAt' => $user->getInvitedAt()?->format(DATE_ATOM),
            'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * Full payload including the subordinates collection. Triggers a lazy
     * load — only call for a single user (detail endpoint), never inside
     * a list loop.
     */
    public function serializeWithSubordinates(User $user): array
    {
        $subordinates = array_map(
            static fn (User $subordinate): array => [
                'id' => $subordinate->getId(),
                'name' => $subordinate->getName(),
            ],
            $user->getSubordinates()->toArray(),
        );

        return [
            ...$this->serialize($user),
            'subordinates' => $subordinates,
            'subordinatesCount' => count($subordinates),
        ];
    }
}
