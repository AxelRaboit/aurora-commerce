<?php

declare(strict_types=1);

namespace Aurora\Module\Planning;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Planning\Setting\PlanningModuleParameterEnum;

final readonly class PlanningContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PlanningModuleParameterEnum::Backend->value);
    }

    public function isPlanningsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PlanningModuleParameterEnum::Plannings->value);
    }
}
