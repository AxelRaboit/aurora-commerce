<?php

declare(strict_types=1);

namespace Aurora\Core;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Media\MediaContext;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;

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
        if (!$this->mediaContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->mediaContext->isLibraryEnabled()) {
            $items[] = new NavItem('backend_media', 'backend.nav.media', 'image', requiredPrivilege: 'media.view', descriptionKey: 'backend.nav.media_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('media', $items, priority: 22)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('media', [
                new NavItem('backend_media', 'backend.nav.media', 'image', requiredPrivilege: 'media.view', descriptionKey: 'backend.nav.media_description'),
            ], priority: 22),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::MediaBackend->toToggle(),
            ModuleParameterEnum::MediaLibrary->toToggle(),
        ];
    }
}
