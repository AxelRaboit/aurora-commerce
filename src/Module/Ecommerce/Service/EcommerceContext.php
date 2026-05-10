<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class EcommerceContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::EcommerceEnabled->value, true);
    }

    public function isFrontEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::EcommerceShopEnabled->value, true);
    }

    public function isListingsEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EcommerceListingsEnabled->value, true);
    }

    public function isOrdersEnabled(): bool
    {
        return $this->isListingsEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EcommerceOrdersEnabled->value, true);
    }
}
