<?php

declare(strict_types=1);

namespace App\Core\Module;

final readonly class NavItem
{
    public function __construct(
        public string $route,
        public string $labelKey,
        public string $icon,
        public ?string $requiredRole = null,
        public string $activeColor = 'accent',
        public ?string $activeRoutePrefix = null,
    ) {}
}
