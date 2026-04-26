<?php

declare(strict_types=1);

namespace App\Core\User\Enum;

enum UserTypeEnum: string
{
    case Admin = 'admin';
    case FrontUser = 'front_user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::FrontUser => 'Utilisateur applicatif',
        };
    }
}
