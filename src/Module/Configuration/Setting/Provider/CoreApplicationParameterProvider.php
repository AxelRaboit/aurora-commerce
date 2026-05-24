<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Setting\Provider;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;

/**
 * Default provider for aurora-core's main `ApplicationParameterEnum`
 * (site name, defaults, OCR / Assistant settings, etc.). Auto-discovered
 * by the `aurora:application-parameter` command via the tagged iterator.
 */
final readonly class CoreApplicationParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from ApplicationParameterEnum::cases();
    }
}
