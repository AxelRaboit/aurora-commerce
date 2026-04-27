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
        $primaryRole = array_find([UserRoleEnum::Admin, UserRoleEnum::Editor, UserRoleEnum::Author, UserRoleEnum::Contributor], fn ($candidate): bool => in_array($candidate->value, $user->getRoles(), true));

        $manager = $user->getManager();

        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'role' => $primaryRole?->value,
            'roleLabel' => $primaryRole?->label(),
            'rolePriority' => $primaryRole?->priority() ?? 0,
            'isDev' => in_array(UserRoleEnum::Dev->value, $user->getRoles(), true),
            'type' => $user->getType()->value,
            'typeLabel' => $user->getType()->label(),
            'status' => $user->getStatus()->value,
            'statusLabel' => $user->getStatus()->label(),
            'locale' => $user->getLocale()->value,
            'profilePhotoUrl' => $user->getProfilePhotoUrl(),
            'managerId' => $manager?->getId(),
            'manager' => null === $manager ? null : ['id' => $manager->getId(), 'name' => $manager->getName()],
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
