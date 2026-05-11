<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

final readonly class NavPermission
{
    public function __construct(
        public string $name,
        /**
         * Optional override for the group the permission is shown under in
         * the privileges modal. Defaults to the declaring module's id
         * (via `ModuleInterface::getId()`). Use it when the privilege
         * conceptually belongs to a different section than the one that
         * declares it — e.g. `core.media.*` is declared by `CoreModule`
         * but conceptually lives in the "Plateforme" section.
         */
        public ?string $group = null,
    ) {}
}
