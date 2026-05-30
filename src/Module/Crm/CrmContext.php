<?php

declare(strict_types=1);

namespace Aurora\Module\Crm;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Crm\Setting\CrmModuleParameterEnum;

final readonly class CrmContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(CrmModuleParameterEnum::Backend->value);
    }

    public function isContactsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(CrmModuleParameterEnum::Contacts->value);
    }

    public function isCompaniesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(CrmModuleParameterEnum::Companies->value);
    }

    public function isDealsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(CrmModuleParameterEnum::Deals->value);
    }
}
