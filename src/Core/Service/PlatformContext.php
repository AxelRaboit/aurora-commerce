<?php

declare(strict_types=1);

namespace Aurora\Core\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

/**
 * Toggle façade for the "Platform" section of the backend (Media, Users,
 * Agencies, Services, Settings, Themes). Each accessor routes through
 * {@see ModuleAccessChecker} so the global setting + per-user override
 * + cascade graph are applied uniformly.
 *
 * The Dashboard (section "core") is intentionally NOT toggle-able: it
 * is the post-login landing page and disabling it would leave a user
 * without a homepage. All other Platform items can be masked globally
 * (dev panel) or per-user (admin user-access picker).
 */
final readonly class PlatformContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::PlatformBackend);
    }

    public function isMediaEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::PlatformMedia);
    }

    public function isUsersEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::PlatformUsers);
    }

    public function isAgenciesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::PlatformAgencies);
    }

    public function isServicesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::PlatformServices);
    }

    public function isSettingsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::PlatformSettings);
    }

    public function isThemesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::PlatformThemes);
    }
}
