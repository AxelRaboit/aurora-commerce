<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Ecommerce\EcommerceContext;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::EcommerceBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EcommerceBackend->value => false])->isBackendEnabled());
    }

    public function testIsFrontEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EcommerceFrontend->value => true])->isFrontEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EcommerceFrontend->value => false])->isFrontEnabled());
    }

    public function testIsListingsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EcommerceListings->value => true])->isListingsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EcommerceListings->value => false])->isListingsEnabled());
    }

    public function testIsOrdersEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EcommerceOrders->value => true])->isOrdersEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EcommerceOrders->value => false])->isOrdersEnabled());
    }
}
