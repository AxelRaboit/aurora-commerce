<?php

declare(strict_types=1);

namespace Aurora\Module\Planning;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Planning\PlanningContext;

final readonly class PlanningModule implements ModuleInterface, ModuleToggleProviderInterface
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
        if (!$this->planningContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->planningContext->isPlanningsEnabled()) {
            $items[] = new NavItem('backend_plannings', 'backend.nav.plannings', 'calendar-days', requiredPrivilege: 'planning.plannings.view', descriptionKey: 'backend.nav.plannings_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('planning', $items, priority: 40)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('planning', [
                new NavItem('backend_plannings', 'backend.nav.plannings', 'calendar-days', requiredPrivilege: 'planning.plannings.view', descriptionKey: 'backend.nav.plannings_description'),
            ], priority: 40),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::PlanningBackend->toToggle(),
            ModuleParameterEnum::PlanningPlannings->toToggle(),
        ];
    }
}
