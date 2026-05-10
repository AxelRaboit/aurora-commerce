<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Vault\Service\VaultContext;
use PHPUnit\Framework\TestCase;

final class VaultContextTest extends TestCase
{
    private function makeContext(array $values): VaultContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new VaultContext($repository);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::VaultEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::VaultEnabled->value => false])->isAdminEnabled());
    }

    public function testIsSafeEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::VaultEnabled->value => true,
            ModuleParameterEnum::VaultSafeEnabled->value => true,
        ]);
        self::assertTrue($context->isSafeEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::VaultEnabled->value => false,
            ModuleParameterEnum::VaultSafeEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isSafeEnabled());
    }

    public function testIsPasswordGeneratorEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::VaultEnabled->value => true,
            ModuleParameterEnum::VaultPasswordGeneratorEnabled->value => true,
        ]);
        self::assertTrue($context->isPasswordGeneratorEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::VaultEnabled->value => false,
            ModuleParameterEnum::VaultPasswordGeneratorEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isPasswordGeneratorEnabled());
    }
}
