<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Hr\HrContext;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::HrBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::HrBackend->value => false])->isBackendEnabled());
    }

    public function testIsEmployeesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::HrEmployees->value => true])->isEmployeesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::HrEmployees->value => false])->isEmployeesEnabled());
    }
}
