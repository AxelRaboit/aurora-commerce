<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Hr module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Hr toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Hr owns
 * its toggles instead of the central ModuleParameterEnum.
 */
final readonly class HrModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from HrModuleParameterEnum::cases();
    }
}
