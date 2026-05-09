<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Service;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Single source of truth for billing module activation.
 *
 * Billing is admin-only (invoices, suppliers, OCR import). It depends on CRM
 * because suppliers reuse the CRM Company concept; the cascade graph in
 * ApplicationParameterEnum enforces this.
 */
final readonly class BillingContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::BillingEnabled->value, true);
    }
}
