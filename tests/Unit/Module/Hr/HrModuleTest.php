<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Hr\HrContext;
use Aurora\Module\Hr\HrModule;
use PHPUnit\Framework\TestCase;

final class HrModuleTest extends TestCase
{
    private function makeModule(bool $backendEnabled = true, bool $employeesEnabled = true): HrModule
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $param): bool => match ($param) {
                ModuleParameterEnum::HrBackend => $backendEnabled,
                ModuleParameterEnum::HrEmployees => $employeesEnabled,
                default => false,
            },
        );

        return new HrModule(new HrContext($checker));
    }

    public function testGetIdReturnsHr(): void
    {
        self::assertSame('hr', $this->makeModule()->getId());
    }

    public function testGetPermissionsContainsEmployeePermissions(): void
    {
        $permissions = $this->makeModule()->getPermissions();

        self::assertCount(4, $permissions);
    }

    public function testGetNavSectionsReturnsEmptyWhenBackendDisabled(): void
    {
        self::assertSame([], $this->makeModule(backendEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsEmptyWhenAllSubFeaturesDisabled(): void
    {
        self::assertSame([], $this->makeModule(employeesEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsSectionWhenEmployeesEnabled(): void
    {
        $sections = $this->makeModule()->getNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetCatalogNavSectionsAlwaysReturnsAllSections(): void
    {
        $sections = $this->makeModule(backendEnabled: false)->getCatalogNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetTogglesReturnsTwoEntries(): void
    {
        self::assertCount(2, $this->makeModule()->getToggles());
    }
}
