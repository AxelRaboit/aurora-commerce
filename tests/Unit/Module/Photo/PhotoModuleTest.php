<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo;

use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Photo\PhotoContext;
use Aurora\Module\Photo\PhotoModule;
use Aurora\Module\Photo\Setting\PhotoModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class PhotoModuleTest extends TestCase
{
    private function makeModule(
        bool $backendEnabled = true,
        bool $galleriesEnabled = true,
    ): PhotoModule {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $param): bool => match ($param) {
                PhotoModuleParameterEnum::Backend->value => $backendEnabled,
                PhotoModuleParameterEnum::Galleries->value => $galleriesEnabled,
                default => false,
            },
        );

        return new PhotoModule(new PhotoContext($checker));
    }

    public function testGetIdReturnsPhoto(): void
    {
        self::assertSame('photo', $this->makeModule()->getId());
    }

    public function testGetPermissionsCountsFour(): void
    {
        self::assertCount(4, $this->makeModule()->getPermissions());
    }

    public function testGetNavSectionsReturnsEmptyWhenBackendDisabled(): void
    {
        self::assertSame([], $this->makeModule(backendEnabled: false)->getNavSections());
    }

    public function testGetNavSectionsReturnsEmptyWhenGalleriesDisabled(): void
    {
        self::assertSame([], $this->makeModule(galleriesEnabled: false)->getNavSections());
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

    public function testGetTogglesReturnsThreeEntries(): void
    {
        self::assertCount(3, $this->makeModule()->getToggles());
    }
}
