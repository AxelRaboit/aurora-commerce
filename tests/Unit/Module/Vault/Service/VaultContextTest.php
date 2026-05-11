<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\Service;

use Aurora\Core\Module\ModuleAccessChecker;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::VaultEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::VaultEnabled->value => false])->isAdminEnabled());
    }

    public function testIsSafeEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::VaultSafeEnabled->value => true])->isSafeEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::VaultSafeEnabled->value => false])->isSafeEnabled());
    }

    public function testIsPasswordGeneratorEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::VaultPasswordGeneratorEnabled->value => true])->isPasswordGeneratorEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::VaultPasswordGeneratorEnabled->value => false])->isPasswordGeneratorEnabled());
    }
}
