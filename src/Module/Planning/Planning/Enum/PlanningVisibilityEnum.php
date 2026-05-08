<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Enum;

enum PlanningVisibilityEnum: string
{
    case Private_ = 'private';
    case Agency = 'agency';
    case Public_ = 'public';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
