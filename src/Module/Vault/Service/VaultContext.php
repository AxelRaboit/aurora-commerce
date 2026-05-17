<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class VaultContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultBackend);
    }

    public function isSafeEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultSafe);
    }

    public function isPasswordGeneratorEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultPasswordGenerator);
    }
}
