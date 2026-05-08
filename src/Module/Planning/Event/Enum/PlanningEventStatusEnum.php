<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Enum;

enum PlanningEventStatusEnum: string
{
    case Tentative = 'tentative';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
