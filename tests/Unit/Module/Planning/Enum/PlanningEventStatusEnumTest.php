<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Enum;

use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use PHPUnit\Framework\TestCase;

final class PlanningEventStatusEnumTest extends TestCase
{
    public function testValuesReturnsAllCasesInDeclarationOrder(): void
    {
        self::assertSame(
            ['tentative', 'confirmed', 'cancelled'],
            PlanningEventStatusEnum::values(),
        );
    }

    public function testTryFromRecognisesAllBackedValues(): void
    {
        foreach (PlanningEventStatusEnum::values() as $value) {
            self::assertInstanceOf(PlanningEventStatusEnum::class, PlanningEventStatusEnum::tryFrom($value));
        }
    }

    public function testTryFromReturnsNullForUnknownValue(): void
    {
        self::assertNull(PlanningEventStatusEnum::tryFrom('bogus'));
    }

    public function testEachCaseHasExpectedStringValue(): void
    {
        self::assertSame('tentative', PlanningEventStatusEnum::Tentative->value);
        self::assertSame('confirmed', PlanningEventStatusEnum::Confirmed->value);
        self::assertSame('cancelled', PlanningEventStatusEnum::Cancelled->value);
    }
}
