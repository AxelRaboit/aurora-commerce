<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Assistant module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Assistant toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Assistant
 * owns its toggles instead of the central ModuleParameterEnum.
 */
final readonly class AssistantModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from AssistantModuleParameterEnum::cases();
    }
}
