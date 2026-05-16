<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Support;

use Aurora\Core\Support\Num;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NumTest extends TestCase
{
    /**
     * @return iterable<string, array{0: int|float, 1: int|float, 2: int|float, 3: int|float}>
     */
    public static function clampCases(): iterable
    {
        yield 'below min returns min'        => [-5, 0, 10, 0];
        yield 'above max returns max'        => [42, 0, 10, 10];
        yield 'inside range passes through'  => [5, 0, 10, 5];
        yield 'equal to min'                 => [0, 0, 10, 0];
        yield 'equal to max'                 => [10, 0, 10, 10];
        yield 'float clamp keeps float'      => [1.5, 0.0, 1.0, 1.0];
        yield 'negative float clamps to min' => [-0.3, 0.0, 1.0, 0.0];
    }

    #[DataProvider('clampCases')]
    public function testClamp(int|float $value, int|float $min, int|float $max, int|float $expected): void
    {
        self::assertSame($expected, Num::clamp($value, $min, $max));
    }

    public function testPercentToRatioOnTypicalValues(): void
    {
        self::assertSame(0.0, Num::percentToRatio(0));
        self::assertSame(0.5, Num::percentToRatio(50));
        self::assertSame(0.85, Num::percentToRatio(85));
        self::assertSame(1.0, Num::percentToRatio(100));
    }

    public function testPercentToRatioClampsOutOfRangeInputs(): void
    {
        self::assertSame(0.0, Num::percentToRatio(-30));
        self::assertSame(1.0, Num::percentToRatio(250));
    }
}
