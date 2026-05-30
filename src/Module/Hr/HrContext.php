<?php

declare(strict_types=1);

namespace Aurora\Module\Hr;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Hr\Setting\HrModuleParameterEnum;

final readonly class HrContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(HrModuleParameterEnum::Backend->value);
    }

    public function isEmployeesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(HrModuleParameterEnum::Employees->value);
    }
}
