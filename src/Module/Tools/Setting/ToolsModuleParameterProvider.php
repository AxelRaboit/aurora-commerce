<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Tools module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Tools toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Tools owns
 * its toggles instead of the central ModuleParameterEnum.
 */
final readonly class ToolsModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from ToolsModuleParameterEnum::cases();
    }
}
