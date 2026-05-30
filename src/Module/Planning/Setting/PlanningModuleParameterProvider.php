<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Planning module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Planning toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Planning
 * owns its toggles instead of the central ModuleParameterEnum.
 */
final readonly class PlanningModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from PlanningModuleParameterEnum::cases();
    }
}
