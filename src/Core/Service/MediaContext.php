<?php

declare(strict_types=1);

namespace Aurora\Core\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

/**
 * Toggle façade for the "Media" section of the backend (the media library).
 * Sibling of {@see PlatformContext} and {@see ConfigurationContext}; split
 * out of Platform in Jalon 4.5 because media is cross-cutting (used by
 * every business module) rather than admin-platform-specific.
 */
final readonly class MediaContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::MediaBackend);
    }

    public function isLibraryEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::MediaLibrary);
    }
}
