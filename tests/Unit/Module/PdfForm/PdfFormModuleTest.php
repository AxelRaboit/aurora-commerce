<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\PdfForm\PdfFormModule;
use Aurora\Module\PdfForm\Service\PdfFormContext;
use PHPUnit\Framework\TestCase;

final class PdfFormModuleTest extends TestCase
{
    private function makeModule(
        bool $backendEnabled = true,
        bool $templatesEnabled = true,
        bool $documentsEnabled = true,
    ): PdfFormModule {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $param): bool => match ($param) {
                ModuleParameterEnum::PdfFormBackend => $backendEnabled,
                ModuleParameterEnum::PdfFormTemplates => $templatesEnabled,
                ModuleParameterEnum::PdfFormDocuments => $documentsEnabled,
                default => false,
            },
        );

        return new PdfFormModule(new PdfFormContext($checker));
    }

    public function testGetIdReturnsPdfform(): void
    {
        self::assertSame('pdfform', $this->makeModule()->getId());
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
            templatesEnabled: false,
            documentsEnabled: false,
        )->getNavSections();

        self::assertSame([], $sections);
    }

    public function testGetNavSectionsReturnsSectionWhenTemplatesEnabled(): void
    {
        $sections = $this->makeModule(documentsEnabled: false)->getNavSections();

        self::assertCount(1, $sections);
        self::assertContainsOnlyInstancesOf(NavSection::class, $sections);
    }

    public function testGetCatalogNavSectionsAlwaysReturnsAllSections(): void
    {
        $sections = $this->makeModule(backendEnabled: false)->getCatalogNavSections();

        self::assertCount(1, $sections);
    }

    public function testGetTogglesReturnsThreeEntries(): void
    {
        self::assertCount(3, $this->makeModule()->getToggles());
    }
}
