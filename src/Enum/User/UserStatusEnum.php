<?php

declare(strict_types=1);

namespace App\Enum\User;

enum UserStatusEnum: string
{
    case Active = 'active';
    case Invited = 'invited';
    case Disabled = 'disabled';
    case PendingVerification = 'pending_verification';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Invited => 'Invité',
            self::Disabled => 'Désactivé',
            self::PendingVerification => 'En attente de vérification',
        };
    }
}
