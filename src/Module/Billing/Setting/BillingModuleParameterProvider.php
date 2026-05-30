<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Billing module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Billing toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Billing owns
 * its toggles instead of the central ModuleParameterEnum.
 */
final readonly class BillingModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from BillingModuleParameterEnum::cases();
    }
}
