<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Project\ProjectContext;
use PHPUnit\Framework\TestCase;

final class ProjectContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): ProjectContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new ProjectContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ProjectBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ProjectBackend->value => false])->isBackendEnabled());
    }

    public function testIsProjectsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ProjectProjects->value => true])->isProjectsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ProjectProjects->value => false])->isProjectsEnabled());
    }
}
