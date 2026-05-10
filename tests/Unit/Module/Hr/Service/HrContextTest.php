<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Hr\Service\HrContext;
use PHPUnit\Framework\TestCase;

final class HrContextTest extends TestCase
{
    private function makeContext(array $values): HrContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new HrContext($repository);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::HrEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::HrEnabled->value => false])->isAdminEnabled());
    }

    public function testIsEmployeesEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::HrEnabled->value => true,
            ModuleParameterEnum::HrEmployeesEnabled->value => true,
        ]);
        self::assertTrue($context->isEmployeesEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::HrEnabled->value => false,
            ModuleParameterEnum::HrEmployeesEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isEmployeesEnabled());
    }
}
