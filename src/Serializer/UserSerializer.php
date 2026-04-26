<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\User;
use App\Enum\User\UserRoleEnum;

use const DATE_ATOM;

final readonly class UserSerializer
{
    public function serialize(User $user): array
    {
        $primaryRole = null;
        foreach ([UserRoleEnum::Admin, UserRoleEnum::Editor, UserRoleEnum::Author, UserRoleEnum::Contributor] as $candidate) {
            if (in_array($candidate->value, $user->getRoles(), true)) {
                $primaryRole = $candidate;
                break;
            }
        }

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
