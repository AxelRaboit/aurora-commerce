<?php

declare(strict_types=1);

namespace Aurora\Module\Planning;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Module\Planning\Service\PlanningContext;

final readonly class PlanningModule implements ModuleInterface
{
    public function __construct(private PlanningContext $planningContext) {}

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
        if (!$this->planningContext->isAdminEnabled()) {
            return [];
        }

        return [
            new NavSection('planning', [
                new NavItem('backend_plannings', 'backend.nav.plannings', 'calendar-days', descriptionKey: 'backend.nav.plannings_description'),
            ], priority: 40),
        ];
    }
}
