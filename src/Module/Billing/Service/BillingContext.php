<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class BillingContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isAdminEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::BillingEnabled);
    }

    public function isTiersEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::BillingTiersEnabled);
    }

    public function isInvoicesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::BillingInvoicesEnabled);
    }

    public function isComplianceEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::BillingComplianceEnabled);
    }
}
