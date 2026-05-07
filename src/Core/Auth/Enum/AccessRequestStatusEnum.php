<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Enum;

enum AccessRequestStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabelKey(): string
    {
        return 'backend.access_requests.status_'.$this->value;
    }
}
