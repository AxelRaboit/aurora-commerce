<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Gallery\Enum;

use Aurora\Module\Photo\Gallery\Enum\PickKindEnum;
use PHPUnit\Framework\TestCase;

final class PickKindEnumTest extends TestCase
{
    public function testValuesReturnsAllCases(): void
    {
        self::assertSame(['favorite', 'print', 'discard'], PickKindEnum::values());
    }

    public function testCaseValues(): void
    {
        self::assertSame('favorite', PickKindEnum::Favorite->value);
        self::assertSame('print', PickKindEnum::Print->value);
        self::assertSame('discard', PickKindEnum::Discard->value);
    }
}
