<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Billing\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Billing\BillingContext;
use Aurora\Module\Billing\Setting\BillingModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class BillingContextTest extends TestCase
{
    /** @param array<string, bool> $values mapping BillingModuleParameterEnum::value => final isEnabled() outcome */
    private function makeContext(array $values): BillingContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new BillingContext($checker);
    }

    public function testIsAdminEnabledTrue(): void
    {
        $context = $this->makeContext([BillingModuleParameterEnum::Backend->value => true]);
        self::assertTrue($context->isBackendEnabled());
    }

    public function testIsAdminEnabledFalse(): void
    {
        $context = $this->makeContext([BillingModuleParameterEnum::Backend->value => false]);
        self::assertFalse($context->isBackendEnabled());
    }

    public function testIsTiersEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            BillingModuleParameterEnum::Tiers->value => true,
        ])->isTiersEnabled());

        self::assertFalse($this->makeContext([
            BillingModuleParameterEnum::Tiers->value => false,
        ])->isTiersEnabled());
    }

    public function testIsInvoicesEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            BillingModuleParameterEnum::Invoices->value => true,
        ])->isInvoicesEnabled());

        self::assertFalse($this->makeContext([
            BillingModuleParameterEnum::Invoices->value => false,
        ])->isInvoicesEnabled());
    }

    public function testIsComplianceEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            BillingModuleParameterEnum::Compliance->value => true,
        ])->isComplianceEnabled());

        self::assertFalse($this->makeContext([
            BillingModuleParameterEnum::Compliance->value => false,
        ])->isComplianceEnabled());
    }
}
