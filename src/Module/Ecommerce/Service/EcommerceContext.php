<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class EcommerceContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isAdminEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EcommerceEnabled);
    }

    public function isFrontEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EcommerceShopEnabled);
    }

    public function isListingsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EcommerceListingsEnabled);
    }

    public function isOrdersEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EcommerceOrdersEnabled);
    }
}
