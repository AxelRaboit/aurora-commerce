<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Ecommerce\Service\EcommerceContext;
use PHPUnit\Framework\TestCase;

final class EcommerceContextTest extends TestCase
{
    private function makeContext(array $values): EcommerceContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new EcommerceContext($repository);
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

    public function testIsListingsEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::EcommerceEnabled->value => true,
            ModuleParameterEnum::EcommerceListingsEnabled->value => true,
        ]);
        self::assertTrue($context->isListingsEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::EcommerceEnabled->value => false,
            ModuleParameterEnum::EcommerceListingsEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isListingsEnabled());
    }

    public function testIsOrdersEnabledRequiresListings(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::EcommerceEnabled->value => true,
            ModuleParameterEnum::EcommerceListingsEnabled->value => true,
            ModuleParameterEnum::EcommerceOrdersEnabled->value => true,
        ]);
        self::assertTrue($context->isOrdersEnabled());

        $contextListingsOff = $this->makeContext([
            ModuleParameterEnum::EcommerceEnabled->value => true,
            ModuleParameterEnum::EcommerceListingsEnabled->value => false,
            ModuleParameterEnum::EcommerceOrdersEnabled->value => true,
        ]);
        self::assertFalse($contextListingsOff->isOrdersEnabled());
    }
}
