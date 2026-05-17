<?php

declare(strict_types=1);

namespace Aurora\Module\Billing;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class BillingContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::BillingBackend);
    }

    public function isTiersEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::BillingTiers);
    }

    public function isInvoicesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::BillingInvoices);
    }

    public function isComplianceEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::BillingCompliance);
    }
}
