<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Sequence\Entity\SequenceCounter;
use PHPUnit\Framework\TestCase;

final class SequenceCounterTest extends TestCase
{
    public function testConstructorAssignsValues(): void
    {
        $counter = new SequenceCounter('FAC', 2026, 42);

        self::assertSame('FAC', $counter->prefix);
        self::assertSame(2026, $counter->year);
        self::assertSame(42, $counter->lastValue);
    }

    public function testDefaultYearAndLastValue(): void
    {
        $counter = new SequenceCounter('ORD');

        self::assertSame('ORD', $counter->prefix);
        self::assertSame(0, $counter->year);
        self::assertSame(0, $counter->lastValue);
    }

    public function testLastValueIsMutable(): void
    {
        $counter = new SequenceCounter('LOG', 0, 5);

        $counter->lastValue = 10;
        self::assertSame(10, $counter->lastValue);
    }
}
