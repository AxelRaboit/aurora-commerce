<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Service;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Single source of truth for HR module activation.
 */
final readonly class HrContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::HrEnabled->value, true);
    }
}
