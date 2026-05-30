<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Crm module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Crm toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Crm owns
 * its toggles instead of the central ModuleParameterEnum.
 */
final readonly class CrmModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from CrmModuleParameterEnum::cases();
    }
}
