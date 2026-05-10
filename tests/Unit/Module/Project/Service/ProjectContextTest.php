<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Project\Service\ProjectContext;
use PHPUnit\Framework\TestCase;

final class ProjectContextTest extends TestCase
{
    private function makeContext(array $values): ProjectContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new ProjectContext($repository);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ProjectEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ProjectEnabled->value => false])->isAdminEnabled());
    }

    public function testIsProjectsEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::ProjectEnabled->value => true,
            ModuleParameterEnum::ProjectProjectsEnabled->value => true,
        ]);
        self::assertTrue($context->isProjectsEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::ProjectEnabled->value => false,
            ModuleParameterEnum::ProjectProjectsEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isProjectsEnabled());
    }
}
