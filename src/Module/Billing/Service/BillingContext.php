<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class BillingContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::BillingEnabled->value, true);
    }

    public function isTiersEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::BillingTiersEnabled->value, true);
    }

    public function isInvoicesEnabled(): bool
    {
        return $this->isTiersEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::BillingInvoicesEnabled->value, true);
    }

    public function isComplianceEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::BillingComplianceEnabled->value, true);
    }
}
