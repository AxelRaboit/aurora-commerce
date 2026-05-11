<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Hr\Service\HrContext;
use PHPUnit\Framework\TestCase;

final class HrContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): HrContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new HrContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::HrEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::HrEnabled->value => false])->isAdminEnabled());
    }

    public function testIsEmployeesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::HrEmployeesEnabled->value => true])->isEmployeesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::HrEmployeesEnabled->value => false])->isEmployeesEnabled());
    }
}
