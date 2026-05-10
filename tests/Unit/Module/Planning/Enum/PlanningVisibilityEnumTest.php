<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Enum;

use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use PHPUnit\Framework\TestCase;

final class PlanningVisibilityEnumTest extends TestCase
{
    public function testValuesReturnsAllCasesInDeclarationOrder(): void
    {
        self::assertSame(
            ['private', 'agency', 'public'],
            PlanningVisibilityEnum::values(),
        );
    }

    public function testTryFromRecognisesAllBackedValues(): void
    {
        foreach (PlanningVisibilityEnum::values() as $value) {
            self::assertInstanceOf(PlanningVisibilityEnum::class, PlanningVisibilityEnum::tryFrom($value));
        }
    }

    public function testTryFromReturnsNullForUnknownValue(): void
    {
        self::assertNull(PlanningVisibilityEnum::tryFrom('bogus'));
    }

    public function testEachCaseHasExpectedStringValue(): void
    {
        self::assertSame('private', PlanningVisibilityEnum::Private_->value);
        self::assertSame('agency', PlanningVisibilityEnum::Agency->value);
        self::assertSame('public', PlanningVisibilityEnum::Public_->value);
    }
}
