<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class VaultContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isAdminEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultEnabled);
    }

    public function isSafeEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultSafeEnabled);
    }

    public function isPasswordGeneratorEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultPasswordGeneratorEnabled);
    }
}
