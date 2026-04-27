<?php

declare(strict_types=1);

namespace Aurora\Core\User\Serializer;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;

use const DATE_ATOM;

final readonly class UserSerializer
{
    public function serialize(User $user): array
    {
        $primaryRole = array_find([UserRoleEnum::Admin, UserRoleEnum::Editor, UserRoleEnum::Author, UserRoleEnum::Contributor], fn ($candidate): bool => in_array($candidate->value, $user->getRoles(), true));

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
            'invitedAt' => $user->getInvitedAt()?->format(DATE_ATOM),
            'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
