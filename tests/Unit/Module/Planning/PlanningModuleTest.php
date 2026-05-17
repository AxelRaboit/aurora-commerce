<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Planning\PlanningModule;
use Aurora\Module\Planning\PlanningContext;
use PHPUnit\Framework\TestCase;

final class PlanningModuleTest extends TestCase
{
    private function makeModule(bool $backendEnabled = true, bool $planningsEnabled = true): PlanningModule
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $param): bool => match ($param) {
                ModuleParameterEnum::PlanningBackend => $backendEnabled,
                ModuleParameterEnum::PlanningPlannings => $planningsEnabled,
                default => false,
            },
        );

        return new PlanningModule(new PlanningContext($checker));
    }

    public function testGetIdReturnsPlanning(): void
    {
        self::assertSame('planning', $this->makeModule()->getId());
    }

    public function testGetPermissionsCountsSeven(): void
    {
        self::assertCount(7, $this->makeModule()->getPermissions());
    }

    public function testGetNavSectionsReturnsEmptyWhenBackendDisabled(): void
    {
        self::assertSame([], $this->makeModule(backendEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsEmptyWhenPlanningsDisabled(): void
    {
        self::assertSame([], $this->makeModule(planningsEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsSectionWhenEnabled(): void
    {
        $sections = $this->makeModule()->getNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetCatalogNavSectionsAlwaysReturnsSection(): void
    {
        self::assertCount(1, $this->makeModule(backendEnabled: false)->getCatalogNavSections());
    }

    public function testGetTogglesReturnsTwoEntries(): void
    {
        self::assertCount(2, $this->makeModule()->getToggles());
    }
}
