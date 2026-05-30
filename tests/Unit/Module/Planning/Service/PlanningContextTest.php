<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Planning\PlanningContext;
use Aurora\Module\Planning\Setting\PlanningModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class PlanningContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): PlanningContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new PlanningContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([PlanningModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([PlanningModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsPlanningsEnabled(): void
    {
        self::assertTrue($this->makeContext([PlanningModuleParameterEnum::Plannings->value => true])->isPlanningsEnabled());
        self::assertFalse($this->makeContext([PlanningModuleParameterEnum::Plannings->value => false])->isPlanningsEnabled());
    }
}
