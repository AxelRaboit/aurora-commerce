<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class PlanningContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::PlanningEnabled->value, true);
    }

    public function isPlanningsEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::PlanningPlanningsEnabled->value, true);
    }
}
