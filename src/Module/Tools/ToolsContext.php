<?php

declare(strict_types=1);

namespace Aurora\Module\Tools;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class ToolsContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ToolsBackend);
    }

    public function isVaultEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ToolsVault);
    }

    public function isPasswordGeneratorEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ToolsPasswordGenerator);
    }
}
