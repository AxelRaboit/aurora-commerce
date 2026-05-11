<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Planning\Service\PlanningContext;
use PHPUnit\Framework\TestCase;

final class PlanningContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): PlanningContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new PlanningContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PlanningEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PlanningEnabled->value => false])->isAdminEnabled());
    }

    public function testIsPlanningsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PlanningPlanningsEnabled->value => true])->isPlanningsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PlanningPlanningsEnabled->value => false])->isPlanningsEnabled());
    }
}
