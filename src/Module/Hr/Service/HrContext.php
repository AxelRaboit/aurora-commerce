<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class HrContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::HrBackend);
    }

    public function isEmployeesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::HrEmployees);
    }
}
