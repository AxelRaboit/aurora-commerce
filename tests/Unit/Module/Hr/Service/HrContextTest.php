<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Hr\HrContext;
use Aurora\Module\Hr\Setting\HrModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class HrContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): HrContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new HrContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([HrModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([HrModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsEmployeesEnabled(): void
    {
        self::assertTrue($this->makeContext([HrModuleParameterEnum::Employees->value => true])->isEmployeesEnabled());
        self::assertFalse($this->makeContext([HrModuleParameterEnum::Employees->value => false])->isEmployeesEnabled());
    }
}
