<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Enum;

enum PersonalFinanceBudgetPresetApplyModeEnum: string
{
    case Append = 'append';
    case Replace = 'replace';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
