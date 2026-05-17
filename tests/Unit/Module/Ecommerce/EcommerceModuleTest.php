<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Ecommerce\EcommerceModule;
use Aurora\Module\Ecommerce\EcommerceContext;
use PHPUnit\Framework\TestCase;

final class EcommerceModuleTest extends TestCase
{
    private function makeModule(
        bool $backendEnabled = true,
        bool $listingsEnabled = true,
        bool $ordersEnabled = true,
    ): EcommerceModule {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $param): bool => match ($param) {
                ModuleParameterEnum::EcommerceBackend => $backendEnabled,
                ModuleParameterEnum::EcommerceListings => $listingsEnabled,
                ModuleParameterEnum::EcommerceOrders => $ordersEnabled,
                default => false,
            },
        );

        return new EcommerceModule(new EcommerceContext($checker));
    }

    public function testGetIdReturnsEcommerce(): void
    {
        self::assertSame('ecommerce', $this->makeModule()->getId());
    }

    public function testGetPermissionsCountsSeven(): void
    {
        self::assertCount(7, $this->makeModule()->getPermissions());
    }

    public function testGetNavSectionsReturnsEmptyWhenBackendDisabled(): void
    {
        self::assertSame([], $this->makeModule(backendEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsEmptyWhenAllSubFeaturesDisabled(): void
    {
        $sections = $this->makeModule(
            listingsEnabled: false,
            ordersEnabled: false,
        )->getNavSections();

        self::assertSame([], $sections);
    }

    public function testGetNavSectionsReturnsSectionWhenListingsEnabled(): void
    {
        $sections = $this->makeModule(ordersEnabled: false)->getNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetCatalogNavSectionsAlwaysReturnsSection(): void
    {
        self::assertCount(1, $this->makeModule(backendEnabled: false)->getCatalogNavSections());
    }

    public function testGetTogglesReturnsFourEntries(): void
    {
        self::assertCount(4, $this->makeModule()->getToggles());
    }
}
