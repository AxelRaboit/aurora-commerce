<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Planning\PlanningContext;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::PlanningBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PlanningBackend->value => false])->isBackendEnabled());
    }

    public function testIsPlanningsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PlanningPlannings->value => true])->isPlanningsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PlanningPlannings->value => false])->isPlanningsEnabled());
    }
}
