<?php

declare(strict_types=1);

namespace Aurora\Core\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

/**
 * Toggle façade for the "Platform" section of the backend (Media, Users,
 * Agencies, Services). Each accessor routes through {@see ModuleAccessChecker}
 * so the global setting + per-user override + cascade graph are applied
 * uniformly. Configuration (Settings + Themes) lives in
 * {@see ConfigurationContext} since Jalon 4 split — those are admin-config
 * concerns, not operational-data management.
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
}
