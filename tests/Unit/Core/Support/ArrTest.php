<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Support;

use Aurora\Core\Support\Arr;
use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    public function testPositiveIntsKeepsPositiveValues(): void
    {
        self::assertSame([1, 2, 3], Arr::positiveInts([1, 2, 3]));
    }

    public function testPositiveIntsCoercesStringsToInt(): void
    {
        self::assertSame([1, 2, 3], Arr::positiveInts(['1', '2', '3']));
    }

    public function testPositiveIntsFiltersZeroAndNegative(): void
    {
        self::assertSame([1, 2], Arr::positiveInts([0, 1, -3, 2, -1]));
    }

    public function testPositiveIntsFiltersNonNumeric(): void
    {
        self::assertSame([1, 3], Arr::positiveInts([1, 'abc', '', 3]));
    }

    public function testPositiveIntsReturnsEmptyForNonArray(): void
    {
        self::assertSame([], Arr::positiveInts(null));
        self::assertSame([], Arr::positiveInts('not-an-array'));
        self::assertSame([], Arr::positiveInts(42));
    }

    public function testPositiveIntsReturnsEmptyForEmptyArray(): void
    {
        self::assertSame([], Arr::positiveInts([]));
    }

    public function testPositiveIntsReindexesResult(): void
    {
        $result = Arr::positiveInts(['a' => 1, 'b' => 2, 'c' => -1]);

        self::assertSame([0, 1], array_keys($result));
        self::assertSame([1, 2], $result);
    }
}
