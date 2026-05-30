<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Erp module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Erp toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Erp owns
 * its toggles instead of the central ModuleParameterEnum.
 */
final readonly class ErpModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from ErpModuleParameterEnum::cases();
    }
}
