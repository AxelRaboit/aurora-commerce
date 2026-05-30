<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Assistant\Setting\AssistantModuleParameterEnum;

final readonly class AssistantContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(AssistantModuleParameterEnum::Backend->value);
    }

    public function isChatEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(AssistantModuleParameterEnum::Chat->value);
    }

    public function isMountPointsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(AssistantModuleParameterEnum::MountPoints->value);
    }
}
