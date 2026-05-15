<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Event\Enum;

use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use PHPUnit\Framework\TestCase;

final class PlanningEventStatusEnumTest extends TestCase
{
    public function testValuesReturnsAllCases(): void
    {
        self::assertSame(['tentative', 'confirmed', 'cancelled'], PlanningEventStatusEnum::values());
    }

    public function testCasesHaveExpectedStringValues(): void
    {
        self::assertSame('tentative', PlanningEventStatusEnum::Tentative->value);
        self::assertSame('confirmed', PlanningEventStatusEnum::Confirmed->value);
        self::assertSame('cancelled', PlanningEventStatusEnum::Cancelled->value);
    }
}
