<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class CrmContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::CrmEnabled->value, true);
    }

    public function isContactsEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::CrmContactsEnabled->value, true);
    }

    public function isCompaniesEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::CrmCompaniesEnabled->value, true);
    }

    public function isDealsEnabled(): bool
    {
        return $this->isContactsEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::CrmDealsEnabled->value, true);
    }
}
