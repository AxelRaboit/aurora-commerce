<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRoleEnum: string
{
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';
    case Dev = 'ROLE_DEV';
}
