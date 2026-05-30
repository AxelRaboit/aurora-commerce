<?php

declare(strict_types=1);

namespace Aurora\Module\Billing;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Billing\Setting\BillingModuleParameterEnum;

final readonly class BillingContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(BillingModuleParameterEnum::Backend->value);
    }

    public function isTiersEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(BillingModuleParameterEnum::Tiers->value);
    }

    public function isInvoicesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(BillingModuleParameterEnum::Invoices->value);
    }

    public function isComplianceEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(BillingModuleParameterEnum::Compliance->value);
    }
}
