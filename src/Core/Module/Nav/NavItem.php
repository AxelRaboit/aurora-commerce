<?php

declare(strict_types=1);

namespace Aurora\Core\Module\Nav;

final readonly class NavItem
{
    /**
     * @param string    $route    Symfony route name. **Stable token** : sert d'identifiant immuable
     *                            pour persister les préférences utilisateur
     *                            (`CoreUserInterface::getHiddenNavItems()`). Renommer une route =
     *                            breaking change (perte silencieuse de la préférence côté users).
     * @param NavItem[] $children
     */
    public function __construct(
        public string $route,
        public string $labelKey,
        public string $icon,
        public ?string $requiredPrivilege = null,
        public string $activeColor = 'accent',
        public ?string $activeRoutePrefix = null,
        public array $children = [],
        public ?string $descriptionKey = null,
    ) {}
}
