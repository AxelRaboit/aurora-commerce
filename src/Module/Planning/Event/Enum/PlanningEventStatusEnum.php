<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Enum;

enum PlanningEventStatusEnum: string
{
    case Tentative = 'tentative';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
