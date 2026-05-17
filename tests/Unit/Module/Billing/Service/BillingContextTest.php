<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Billing\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Billing\BillingContext;
use PHPUnit\Framework\TestCase;

final class BillingContextTest extends TestCase
{
    /** @param array<string, bool> $values mapping ModuleParameterEnum::value => final isEnabled() outcome */
    private function makeContext(array $values): BillingContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new BillingContext($checker);
    }

    public function testIsAdminEnabledTrue(): void
    {
        $context = $this->makeContext([ModuleParameterEnum::BillingBackend->value => true]);
        self::assertTrue($context->isBackendEnabled());
    }

    public function testIsAdminEnabledFalse(): void
    {
        $context = $this->makeContext([ModuleParameterEnum::BillingBackend->value => false]);
        self::assertFalse($context->isBackendEnabled());
    }

    public function testIsTiersEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            ModuleParameterEnum::BillingTiers->value => true,
        ])->isTiersEnabled());

        self::assertFalse($this->makeContext([
            ModuleParameterEnum::BillingTiers->value => false,
        ])->isTiersEnabled());
    }

    public function testIsInvoicesEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            ModuleParameterEnum::BillingInvoices->value => true,
        ])->isInvoicesEnabled());

        self::assertFalse($this->makeContext([
            ModuleParameterEnum::BillingInvoices->value => false,
        ])->isInvoicesEnabled());
    }

    public function testIsComplianceEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            ModuleParameterEnum::BillingCompliance->value => true,
        ])->isComplianceEnabled());

        self::assertFalse($this->makeContext([
            ModuleParameterEnum::BillingCompliance->value => false,
        ])->isComplianceEnabled());
    }
}
