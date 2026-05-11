<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Ecommerce\Service\EcommerceContext;
use PHPUnit\Framework\TestCase;

final class EcommerceContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): EcommerceContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new EcommerceContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EcommerceEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EcommerceEnabled->value => false])->isAdminEnabled());
    }

    public function testIsFrontEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EcommerceShopEnabled->value => true])->isFrontEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EcommerceShopEnabled->value => false])->isFrontEnabled());
    }

    public function testIsListingsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EcommerceListingsEnabled->value => true])->isListingsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EcommerceListingsEnabled->value => false])->isListingsEnabled());
    }

    public function testIsOrdersEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EcommerceOrdersEnabled->value => true])->isOrdersEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EcommerceOrdersEnabled->value => false])->isOrdersEnabled());
    }
}
