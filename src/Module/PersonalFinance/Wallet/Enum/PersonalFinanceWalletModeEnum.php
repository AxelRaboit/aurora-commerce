<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Enum;

enum PersonalFinanceWalletModeEnum: string
{
    case Budget = 'budget';
    case Simple = 'simple';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
