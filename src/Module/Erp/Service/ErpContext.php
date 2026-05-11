<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class ErpContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ErpBackend);
    }

    public function isProductsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ErpProducts);
    }
}
