<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the PersonalFinance module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the PersonalFinance
 * toggle rows in core_settings are not flagged obsolete (and wiped) once
 * PersonalFinance owns its toggles instead of the central ModuleParameterEnum.
 */
final readonly class PersonalFinanceModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from PersonalFinanceModuleParameterEnum::cases();
    }
}
