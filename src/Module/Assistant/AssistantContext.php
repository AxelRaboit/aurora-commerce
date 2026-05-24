<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class AssistantContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::AssistantBackend);
    }

    public function isChatEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::AssistantChat);
    }

    public function isMountPointsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::AssistantMountPoints);
    }
}
