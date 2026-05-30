<?php

declare(strict_types=1);

namespace Aurora\Module\Tools;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Tools\Setting\ToolsModuleParameterEnum;

final readonly class ToolsContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ToolsModuleParameterEnum::Backend->value);
    }

    public function isVaultEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ToolsModuleParameterEnum::Vault->value);
    }

    public function isPasswordGeneratorEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ToolsModuleParameterEnum::PasswordGenerator->value);
    }
}
