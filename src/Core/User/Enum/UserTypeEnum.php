<?php

declare(strict_types=1);

namespace Aurora\Core\User\Enum;

enum UserTypeEnum: string
{
    case Backend = 'backend';
    case Frontend = 'frontend';

    public function label(): string
    {
        return match ($this) {
            self::Backend => 'Utilisateur Backend',
            self::Frontend => 'Utilisateur Frontend',
        };
    }
}
