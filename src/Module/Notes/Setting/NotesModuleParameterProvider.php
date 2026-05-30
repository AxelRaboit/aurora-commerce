<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Notes module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Notes toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Notes owns
 * its toggles instead of the central ModuleParameterEnum.
 */
final readonly class NotesModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from NotesModuleParameterEnum::cases();
    }
}
