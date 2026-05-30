<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Money\Enum;

use Aurora\Core\Money\Enum\CurrencyEnum;
use PHPUnit\Framework\TestCase;

final class CurrencyEnumTest extends TestCase
{
    public function testSymbolReturnsCorrectGlyph(): void
    {
        self::assertSame('€', CurrencyEnum::EUR->symbol());
        self::assertSame('$', CurrencyEnum::USD->symbol());
        self::assertSame('$', CurrencyEnum::CAD->symbol());
        self::assertSame('$', CurrencyEnum::AUD->symbol());
        self::assertSame('£', CurrencyEnum::GBP->symbol());
        self::assertSame('CHF', CurrencyEnum::CHF->symbol());
        self::assertSame('¥', CurrencyEnum::JPY->symbol());
        self::assertSame('kr', CurrencyEnum::SEK->symbol());
    }

    public function testDecimalsReturnsZeroForJpyAndTwoForOthers(): void
    {
        self::assertSame(0, CurrencyEnum::JPY->decimals());
        self::assertSame(2, CurrencyEnum::EUR->decimals());
        self::assertSame(2, CurrencyEnum::USD->decimals());
        self::assertSame(2, CurrencyEnum::GBP->decimals());
        self::assertSame(2, CurrencyEnum::CHF->decimals());
    }
}
