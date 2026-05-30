<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Tools;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Tools\Setting\ToolsModuleParameterEnum;
use Aurora\Module\Tools\ToolsContext;
use PHPUnit\Framework\TestCase;

final class ToolsContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): ToolsContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new ToolsContext($checker);
    }

    public function testIsBackendEnabled(): void
    {
        self::assertTrue($this->makeContext([ToolsModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ToolsModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsVaultEnabled(): void
    {
        self::assertTrue($this->makeContext([ToolsModuleParameterEnum::Vault->value => true])->isVaultEnabled());
        self::assertFalse($this->makeContext([ToolsModuleParameterEnum::Vault->value => false])->isVaultEnabled());
    }

    public function testIsPasswordGeneratorEnabled(): void
    {
        self::assertTrue($this->makeContext([ToolsModuleParameterEnum::PasswordGenerator->value => true])->isPasswordGeneratorEnabled());
        self::assertFalse($this->makeContext([ToolsModuleParameterEnum::PasswordGenerator->value => false])->isPasswordGeneratorEnabled());
    }
}
