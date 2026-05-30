<?php

declare(strict_types=1);

namespace Aurora\Core\Money\Enum;

enum CurrencyEnum: string
{
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    case CHF = 'CHF';
    case JPY = 'JPY';
    case CAD = 'CAD';
    case AUD = 'AUD';
    case SEK = 'SEK';

    public function symbol(): string
    {
        return match ($this) {
            self::EUR => '€',
            self::USD, self::CAD, self::AUD => '$',
            self::GBP => '£',
            self::CHF => 'CHF',
            self::JPY => '¥',
            self::SEK => 'kr',
        };
    }

    /** Number of decimal places used for human-readable prices in this currency. */
    public function decimals(): int
    {
        return match ($this) {
            self::JPY => 0,
            default => 2,
        };
    }
}
