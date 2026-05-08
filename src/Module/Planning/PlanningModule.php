<?php

declare(strict_types=1);

namespace Aurora\Module\Planning;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;

final readonly class PlanningModule implements ModuleInterface
{
    public function getId(): string
    {
        return 'planning';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('planning.plannings.view'),
            new NavPermission('planning.plannings.manage'),
            new NavPermission('planning.events.manage'),
        ];
    }

    public function getNavSections(): array
    {
        return [
            new NavSection('planning', [
                new NavItem('backend_plannings', 'backend.nav.plannings', 'calendar-days', descriptionKey: 'backend.nav.plannings_description'),
            ], priority: 40),
        ];
    }
}
