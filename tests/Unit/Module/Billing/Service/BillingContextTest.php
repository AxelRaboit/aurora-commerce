<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Billing\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Billing\Service\BillingContext;
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
        $context = $this->makeContext([ModuleParameterEnum::BillingEnabled->value => true]);
        self::assertTrue($context->isAdminEnabled());
    }

    public function testIsAdminEnabledFalse(): void
    {
        $context = $this->makeContext([ModuleParameterEnum::BillingEnabled->value => false]);
        self::assertFalse($context->isAdminEnabled());
    }

    public function testIsTiersEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            ModuleParameterEnum::BillingTiersEnabled->value => true,
        ])->isTiersEnabled());

        self::assertFalse($this->makeContext([
            ModuleParameterEnum::BillingTiersEnabled->value => false,
        ])->isTiersEnabled());
    }

    public function testIsInvoicesEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            ModuleParameterEnum::BillingInvoicesEnabled->value => true,
        ])->isInvoicesEnabled());

        self::assertFalse($this->makeContext([
            ModuleParameterEnum::BillingInvoicesEnabled->value => false,
        ])->isInvoicesEnabled());
    }

    public function testIsComplianceEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([
            ModuleParameterEnum::BillingComplianceEnabled->value => true,
        ])->isComplianceEnabled());

        self::assertFalse($this->makeContext([
            ModuleParameterEnum::BillingComplianceEnabled->value => false,
        ])->isComplianceEnabled());
    }
}
