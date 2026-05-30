<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Ecommerce module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Ecommerce toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Ecommerce
 * owns its toggles instead of the central ModuleParameterEnum.
 */
final readonly class EcommerceModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from EcommerceModuleParameterEnum::cases();
    }
}
