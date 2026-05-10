<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class GedContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::GedEnabled->value, true);
    }

    public function isDocumentsEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::GedDocumentsEnabled->value, true);
    }

    public function isCategoriesEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::GedCategoriesEnabled->value, true);
    }
}
