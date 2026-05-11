<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Vault\Service\VaultContext;
use PHPUnit\Framework\TestCase;

final class VaultContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): VaultContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new VaultContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::VaultBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::VaultBackend->value => false])->isBackendEnabled());
    }

    public function testIsSafeEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::VaultSafe->value => true])->isSafeEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::VaultSafe->value => false])->isSafeEnabled());
    }

    public function testIsPasswordGeneratorEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::VaultPasswordGenerator->value => true])->isPasswordGeneratorEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::VaultPasswordGenerator->value => false])->isPasswordGeneratorEnabled());
    }
}
