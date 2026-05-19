<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Erp;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Erp\ErpContext;
use Aurora\Module\Erp\ErpModule;
use PHPUnit\Framework\TestCase;

final class ErpModuleTest extends TestCase
{
    private function makeModule(bool $backendEnabled = true, bool $productsEnabled = true): ErpModule
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $param): bool => match ($param) {
                ModuleParameterEnum::ErpBackend => $backendEnabled,
                ModuleParameterEnum::ErpProducts => $productsEnabled,
                default => false,
            },
        );

        return new ErpModule(new ErpContext($checker));
    }

    public function testGetIdReturnsErp(): void
    {
        self::assertSame('erp', $this->makeModule()->getId());
    }

    public function testGetPermissionsCountsFour(): void
    {
        self::assertCount(4, $this->makeModule()->getPermissions());
    }

    public function testGetNavSectionsReturnsEmptyWhenBackendDisabled(): void
    {
        self::assertSame([], $this->makeModule(backendEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsEmptyWhenProductsDisabled(): void
    {
        self::assertSame([], $this->makeModule(productsEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsSectionWhenEnabled(): void
    {
        $sections = $this->makeModule()->getNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetCatalogNavSectionsAlwaysReturnsSection(): void
    {
        $sections = $this->makeModule(backendEnabled: false)->getCatalogNavSections();

        self::assertCount(1, $sections);
    }

    public function testGetTogglesReturnsTwoEntries(): void
    {
        self::assertCount(2, $this->makeModule()->getToggles());
    }
}
