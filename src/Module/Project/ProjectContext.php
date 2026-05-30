<?php

declare(strict_types=1);

namespace Aurora\Module\Project;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Project\Setting\ProjectModuleParameterEnum;

final readonly class ProjectContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ProjectModuleParameterEnum::Backend->value);
    }

    public function isProjectsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ProjectModuleParameterEnum::Projects->value);
    }
}
