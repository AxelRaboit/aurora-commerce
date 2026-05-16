<?php

declare(strict_types=1);

namespace Aurora\Core\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

/**
 * Toggle façade for the "Platform" section of the backend (Users, Agencies,
 * Services — the organization layer). Media moved to {@see MediaContext}
 * in Jalon 4.5 since it's cross-cutting infrastructure used by every
 * module. Configuration (Settings + Themes) lives in
 * {@see ConfigurationContext} since the earlier Jalon 4 split.
 */
final readonly class PlatformContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::PlatformBackend);
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
