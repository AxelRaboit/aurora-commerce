<?php

declare(strict_types=1);

namespace App\Module\Crm\Service;

use App\Core\Setting\Enum\ApplicationParameterEnum;
use App\Core\Setting\Repository\SettingRepository;

/**
 * Single source of truth for CRM module activation.
 *
 * CRM is admin-only (no front-facing routes), so a single isAdminEnabled() toggle suffices.
 */
final readonly class CrmContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::CrmAdminEnabled->value, true);
    }
}
