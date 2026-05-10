<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class VaultContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::VaultEnabled->value, true);
    }

    public function isSafeEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::VaultSafeEnabled->value, true);
    }

    public function isPasswordGeneratorEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::VaultPasswordGeneratorEnabled->value, true);
    }
}
