<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Service;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Single source of truth for Planning module activation.
 */
final readonly class PlanningContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::PlanningEnabled->value, true);
    }
}
