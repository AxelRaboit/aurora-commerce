<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Tools;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Tools\ToolsContext;
use PHPUnit\Framework\TestCase;

final class ToolsContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): ToolsContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new ToolsContext($checker);
    }

    public function testIsBackendEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ToolsBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ToolsBackend->value => false])->isBackendEnabled());
    }

    public function testIsVaultEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ToolsVault->value => true])->isVaultEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ToolsVault->value => false])->isVaultEnabled());
    }

    public function testIsPasswordGeneratorEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ToolsPasswordGenerator->value => true])->isPasswordGeneratorEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ToolsPasswordGenerator->value => false])->isPasswordGeneratorEnabled());
    }
}
