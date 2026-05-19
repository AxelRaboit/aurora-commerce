<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Crm\CrmContext;
use Aurora\Module\Crm\CrmModule;
use PHPUnit\Framework\TestCase;

final class CrmModuleTest extends TestCase
{
    private function makeModule(
        bool $backendEnabled = true,
        bool $contactsEnabled = true,
        bool $companiesEnabled = true,
        bool $dealsEnabled = true,
    ): CrmModule {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $param): bool => match ($param) {
                ModuleParameterEnum::CrmBackend => $backendEnabled,
                ModuleParameterEnum::CrmContacts => $contactsEnabled,
                ModuleParameterEnum::CrmCompanies => $companiesEnabled,
                ModuleParameterEnum::CrmDeals => $dealsEnabled,
                default => false,
            },
        );

        return new CrmModule(new CrmContext($checker));
    }

    public function testGetIdReturnsCrm(): void
    {
        self::assertSame('crm', $this->makeModule()->getId());
    }

    public function testGetPermissionsCountsTwelve(): void
    {
        self::assertCount(12, $this->makeModule()->getPermissions());
    }

    public function testGetNavSectionsReturnsEmptyWhenBackendDisabled(): void
    {
        self::assertSame([], $this->makeModule(backendEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsEmptyWhenAllSubFeaturesDisabled(): void
    {
        $sections = $this->makeModule(
            contactsEnabled: false,
            companiesEnabled: false,
            dealsEnabled: false,
        )->getNavSections();

        self::assertSame([], $sections);
    }

    public function testGetNavSectionsReturnsSectionWhenContactsEnabled(): void
    {
        $sections = $this->makeModule(
            companiesEnabled: false,
            dealsEnabled: false,
        )->getNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetCatalogNavSectionsAlwaysReturnsAllSections(): void
    {
        $sections = $this->makeModule(backendEnabled: false)->getCatalogNavSections();

        self::assertCount(1, $sections);
    }

    public function testGetTogglesReturnsFourEntries(): void
    {
        self::assertCount(4, $this->makeModule()->getToggles());
    }
}
