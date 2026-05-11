<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class CrmContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isAdminEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::CrmEnabled);
    }

    public function isContactsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::CrmContactsEnabled);
    }

    public function isCompaniesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::CrmCompaniesEnabled);
    }

    public function isDealsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::CrmDealsEnabled);
    }
}
