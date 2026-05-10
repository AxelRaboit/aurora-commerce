<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Billing\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Billing\Service\BillingContext;
use PHPUnit\Framework\TestCase;

final class BillingContextTest extends TestCase
{
    private function makeContext(array $values): BillingContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new BillingContext($repository);
    }

    public function testIsAdminEnabledTrue(): void
    {
        $context = $this->makeContext([ModuleParameterEnum::BillingEnabled->value => true]);
        self::assertTrue($context->isAdminEnabled());
    }

    public function testIsAdminEnabledFalse(): void
    {
        $context = $this->makeContext([ModuleParameterEnum::BillingEnabled->value => false]);
        self::assertFalse($context->isAdminEnabled());
    }

    public function testIsTiersEnabledWhenBothEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::BillingEnabled->value => true,
            ModuleParameterEnum::BillingTiersEnabled->value => true,
        ]);
        self::assertTrue($context->isTiersEnabled());
    }

    public function testIsTiersEnabledFalseWhenAdminDisabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::BillingEnabled->value => false,
            ModuleParameterEnum::BillingTiersEnabled->value => true,
        ]);
        self::assertFalse($context->isTiersEnabled());
    }

    public function testIsInvoicesEnabledWhenAllEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::BillingEnabled->value => true,
            ModuleParameterEnum::BillingTiersEnabled->value => true,
            ModuleParameterEnum::BillingInvoicesEnabled->value => true,
        ]);
        self::assertTrue($context->isInvoicesEnabled());
    }

    public function testIsInvoicesEnabledFalseWhenTiersDisabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::BillingEnabled->value => true,
            ModuleParameterEnum::BillingTiersEnabled->value => false,
            ModuleParameterEnum::BillingInvoicesEnabled->value => true,
        ]);
        self::assertFalse($context->isInvoicesEnabled());
    }

    public function testIsComplianceEnabledWhenBothEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::BillingEnabled->value => true,
            ModuleParameterEnum::BillingComplianceEnabled->value => true,
        ]);
        self::assertTrue($context->isComplianceEnabled());
    }

    public function testIsComplianceEnabledFalseWhenAdminDisabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::BillingEnabled->value => false,
            ModuleParameterEnum::BillingComplianceEnabled->value => true,
        ]);
        self::assertFalse($context->isComplianceEnabled());
    }
}
