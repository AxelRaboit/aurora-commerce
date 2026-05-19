<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Ged\GedContext;
use Aurora\Module\Ged\GedModule;
use PHPUnit\Framework\TestCase;

final class GedModuleTest extends TestCase
{
    private function makeModule(
        bool $backendEnabled = true,
        bool $documentsEnabled = true,
        bool $categoriesEnabled = true,
        bool $tagsEnabled = true,
        bool $foldersEnabled = true,
    ): GedModule {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $param): bool => match ($param) {
                ModuleParameterEnum::GedBackend => $backendEnabled,
                ModuleParameterEnum::GedDocuments => $documentsEnabled,
                ModuleParameterEnum::GedCategories => $categoriesEnabled,
                ModuleParameterEnum::GedTags => $tagsEnabled,
                ModuleParameterEnum::GedFolders => $foldersEnabled,
                default => false,
            },
        );

        return new GedModule(new GedContext($checker));
    }

    public function testGetIdReturnsGed(): void
    {
        self::assertSame('ged', $this->makeModule()->getId());
    }

    public function testGetPermissionsCountsTen(): void
    {
        self::assertCount(10, $this->makeModule()->getPermissions());
    }

    public function testGetNavSectionsReturnsEmptyWhenBackendDisabled(): void
    {
        self::assertSame([], $this->makeModule(backendEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsEmptyWhenAllSubFeaturesDisabled(): void
    {
        $sections = $this->makeModule(
            documentsEnabled: false,
            categoriesEnabled: false,
            tagsEnabled: false,
            foldersEnabled: false,
        )->getNavSections();

        self::assertSame([], $sections);
    }

    public function testGetNavSectionsReturnsSectionWhenAnyEnabled(): void
    {
        $sections = $this->makeModule(
            categoriesEnabled: false,
            tagsEnabled: false,
            foldersEnabled: false,
        )->getNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetCatalogNavSectionsReturnsAllItems(): void
    {
        $sections = $this->makeModule(backendEnabled: false)->getCatalogNavSections();

        self::assertCount(1, $sections);
    }

    public function testGetTogglesReturnsSixEntries(): void
    {
        self::assertCount(6, $this->makeModule()->getToggles());
    }
}
