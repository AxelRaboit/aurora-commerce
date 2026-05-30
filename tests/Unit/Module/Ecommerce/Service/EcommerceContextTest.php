<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Ecommerce\EcommerceContext;
use Aurora\Module\Ecommerce\Setting\EcommerceModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class EcommerceContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): EcommerceContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new EcommerceContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([EcommerceModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([EcommerceModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsFrontEnabled(): void
    {
        self::assertTrue($this->makeContext([EcommerceModuleParameterEnum::Frontend->value => true])->isFrontEnabled());
        self::assertFalse($this->makeContext([EcommerceModuleParameterEnum::Frontend->value => false])->isFrontEnabled());
    }

    public function testIsListingsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([EcommerceModuleParameterEnum::Listings->value => true])->isListingsEnabled());
        self::assertFalse($this->makeContext([EcommerceModuleParameterEnum::Listings->value => false])->isListingsEnabled());
    }

    public function testIsOrdersEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([EcommerceModuleParameterEnum::Orders->value => true])->isOrdersEnabled());
        self::assertFalse($this->makeContext([EcommerceModuleParameterEnum::Orders->value => false])->isOrdersEnabled());
    }
}
