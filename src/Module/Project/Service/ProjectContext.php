<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class ProjectContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isAdminEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ProjectEnabled);
    }

    public function isProjectsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ProjectProjectsEnabled);
    }
}
