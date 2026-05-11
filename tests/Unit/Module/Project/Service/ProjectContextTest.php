<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Project\Service\ProjectContext;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::ProjectEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ProjectEnabled->value => false])->isAdminEnabled());
    }

    public function testIsProjectsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ProjectProjectsEnabled->value => true])->isProjectsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ProjectProjectsEnabled->value => false])->isProjectsEnabled());
    }
}
