<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Planning\Service\PlanningContext;
use PHPUnit\Framework\TestCase;

final class PlanningContextTest extends TestCase
{
    private function makeContext(array $values): PlanningContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new PlanningContext($repository);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PlanningEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PlanningEnabled->value => false])->isAdminEnabled());
    }

    public function testIsPlanningsEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::PlanningEnabled->value => true,
            ModuleParameterEnum::PlanningPlanningsEnabled->value => true,
        ]);
        self::assertTrue($context->isPlanningsEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::PlanningEnabled->value => false,
            ModuleParameterEnum::PlanningPlanningsEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isPlanningsEnabled());
    }
}
