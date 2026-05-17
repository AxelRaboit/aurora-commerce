<?php

declare(strict_types=1);

namespace Aurora\Module\General;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;

/**
 * Toggle façade for the "Général" backend section (Dashboard).
 * Mirrors the {@see PlatformContext} pattern.
 *
 * When the Dashboard is masked (globally or per-user),
 * {@see GeneralRouteGateSubscriber}
 * redirects any hit on `backend_dashboard` to `backend_profile`
 * so the user always lands on something they can read instead of
 * seeing a 404.
 */
final readonly class GeneralContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::GeneralBackend);
    }

    public function isDashboardEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::GeneralDashboard);
    }
}
