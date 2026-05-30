<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Project\ProjectContext;
use Aurora\Module\Project\Setting\ProjectModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class ProjectContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): ProjectContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new ProjectContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ProjectModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ProjectModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsProjectsEnabled(): void
    {
        self::assertTrue($this->makeContext([ProjectModuleParameterEnum::Projects->value => true])->isProjectsEnabled());
        self::assertFalse($this->makeContext([ProjectModuleParameterEnum::Projects->value => false])->isProjectsEnabled());
    }
}
