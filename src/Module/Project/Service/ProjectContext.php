<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class ProjectContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ProjectBackend);
    }

    public function isProjectsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::ProjectProjects);
    }
}
