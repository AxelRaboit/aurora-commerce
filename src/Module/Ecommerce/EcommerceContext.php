<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Ecommerce\Setting\EcommerceModuleParameterEnum;

final readonly class EcommerceContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EcommerceModuleParameterEnum::Backend->value);
    }

    public function isFrontEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EcommerceModuleParameterEnum::Frontend->value);
    }

    public function isListingsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EcommerceModuleParameterEnum::Listings->value);
    }

    public function isOrdersEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EcommerceModuleParameterEnum::Orders->value);
    }
}
