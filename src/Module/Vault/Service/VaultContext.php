<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\Service;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class VaultContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::VaultEnabled->value, true);
    }
}
