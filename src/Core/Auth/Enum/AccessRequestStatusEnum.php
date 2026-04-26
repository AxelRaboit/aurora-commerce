<?php

declare(strict_types=1);

namespace App\Core\Auth\Enum;

enum AccessRequestStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
