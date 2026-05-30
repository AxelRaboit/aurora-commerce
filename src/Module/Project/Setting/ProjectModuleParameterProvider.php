<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Project module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Project toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Project owns
 * its toggles instead of the central ModuleParameterEnum.
 */
final readonly class ProjectModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from ProjectModuleParameterEnum::cases();
    }
}
