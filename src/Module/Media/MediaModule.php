<?php

declare(strict_types=1);

namespace Aurora\Module\Media;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

/**
 * Media section — the media library (images, videos, files, folders).
 * Cross-cutting infrastructure: every business module (Editorial, Crm,
 * Ecommerce, …) consumes media. Split from {@see PlatformModule} so it
 * has its own toggle root + nav section instead of being a Platform
 * sub-feature — reflects the reality of how it's used.
 */
final readonly class MediaModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private MediaContext $mediaContext) {}

    public function getId(): string
    {
        return 'media';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('media.view'),
            new NavPermission('media.create'),
            new NavPermission('media.edit'),
            new NavPermission('media.delete'),
            new NavPermission('media.folders.create'),
            new NavPermission('media.folders.edit'),
            new NavPermission('media.folders.delete'),
        ];
    }

    public function getNavSections(): array
    {
        // Hidden by Phase 4 of the Media → GED merge — the library is
        // superseded by /backend/ged/documents and Phase 5 drops the
        // module entirely. Routes still resolve so any deep link or
        // pre-Phase-2 client extension keeps loading; only the menu
        // entry is gone.
        return [];
    }

    public function getCatalogNavSections(): array
    {
        return [];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::MediaBackend->toToggle(),
            ModuleParameterEnum::MediaLibrary->toToggle(),
        ];
    }
}
