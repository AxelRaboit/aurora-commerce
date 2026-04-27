<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Service;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Single source of truth for ERP module activation.
 *
 * ERP is admin-only (no front-facing routes), so a single isAdminEnabled() toggle suffices.
 */
final readonly class ErpContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::ErpAdminEnabled->value, true);
    }
}
