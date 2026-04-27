<?php

declare(strict_types=1);

namespace Aurora\Core\Twig;

use Aurora\Core\Module\ModuleRegistry;
use Twig\Attribute\AsTwigFunction;

final readonly class SidebarExtension
{
    public function __construct(private ModuleRegistry $moduleRegistry) {}

    #[AsTwigFunction(name: 'sidebar_nav_sections')]
    public function getSidebarNavSections(): array
    {
        return $this->moduleRegistry->getNavSections();
    }
}
