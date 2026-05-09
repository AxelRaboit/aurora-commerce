<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Service;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Single source of truth for Project module activation.
 */
final readonly class ProjectContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::ProjectEnabled->value, true);
    }
}
