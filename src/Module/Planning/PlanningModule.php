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
            new NavPermission('planning.plannings.create'),
            new NavPermission('planning.plannings.edit'),
            new NavPermission('planning.plannings.delete'),
            new NavPermission('planning.events.create'),
            new NavPermission('planning.events.edit'),
            new NavPermission('planning.events.delete'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->planningContext->isAdminEnabled()) {
            return [];
        }

        return $this->getCatalogNavSections();
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('planning', [
                new NavItem('backend_plannings', 'backend.nav.plannings', 'calendar-days', descriptionKey: 'backend.nav.plannings_description'),
            ], priority: 40),
        ];
    }
}
