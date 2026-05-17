<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Billing;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Billing\BillingModule;
use Aurora\Module\Billing\BillingContext;
use PHPUnit\Framework\TestCase;

final class BillingModuleTest extends TestCase
{
    private function makeModule(
        bool $backendEnabled = true,
        bool $tiersEnabled = true,
        bool $invoicesEnabled = true,
        bool $complianceEnabled = true,
    ): BillingModule {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $param): bool => match ($param) {
                ModuleParameterEnum::BillingBackend => $backendEnabled,
                ModuleParameterEnum::BillingTiers => $tiersEnabled,
                ModuleParameterEnum::BillingInvoices => $invoicesEnabled,
                ModuleParameterEnum::BillingCompliance => $complianceEnabled,
                default => false,
            },
        );

        return new BillingModule(new BillingContext($checker));
    }

    public function testGetIdReturnsBilling(): void
    {
        self::assertSame('billing', $this->makeModule()->getId());
    }

    public function testGetPermissionsCountsEight(): void
    {
        self::assertCount(8, $this->makeModule()->getPermissions());
    }

    public function testGetNavSectionsReturnsEmptyWhenBackendDisabled(): void
    {
        self::assertSame([], $this->makeModule(backendEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsEmptyWhenAllSubFeaturesDisabled(): void
    {
        $sections = $this->makeModule(
            tiersEnabled: false,
            invoicesEnabled: false,
            complianceEnabled: false,
        )->getNavSections();

        self::assertSame([], $sections);
    }

    public function testGetNavSectionsReturnsSectionWhenAnyEnabled(): void
    {
        $sections = $this->makeModule(
            tiersEnabled: false,
            complianceEnabled: false,
        )->getNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetCatalogNavSectionsAlwaysReturnsSection(): void
    {
        self::assertCount(1, $this->makeModule(backendEnabled: false)->getCatalogNavSections());
    }

    public function testGetTogglesIncludesAllParameters(): void
    {
        $toggles = $this->makeModule()->getToggles();

        self::assertGreaterThanOrEqual(4, count($toggles));
    }
}
