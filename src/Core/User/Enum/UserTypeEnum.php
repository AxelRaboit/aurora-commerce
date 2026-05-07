<?php

declare(strict_types=1);

namespace Aurora\Core\User\Enum;

enum UserTypeEnum: string
{
    case Backend = 'backend';
    case Frontend = 'frontend';

    public function getLabelKey(): string
    {
        return 'backend.users.type.'.$this->value;
    }
}
