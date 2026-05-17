<?php

declare(strict_types=1);

namespace Aurora\Module\Project;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Project\Service\ProjectContext;

final readonly class ProjectModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private ProjectContext $projectContext) {}

    public function getId(): string
    {
        return 'project';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('project.projects.view'),
            new NavPermission('project.projects.create'),
            new NavPermission('project.projects.edit'),
            new NavPermission('project.projects.delete'),
            new NavPermission('project.tasks.manage'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->projectContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->projectContext->isProjectsEnabled()) {
            $items[] = new NavItem('backend_projects', 'backend.nav.projects', 'folder-kanban', requiredPrivilege: 'project.projects.view', descriptionKey: 'backend.nav.projects_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('project', $items, priority: 35)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('project', [
                new NavItem('backend_projects', 'backend.nav.projects', 'folder-kanban', requiredPrivilege: 'project.projects.view', descriptionKey: 'backend.nav.projects_description'),
            ], priority: 35),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::ProjectBackend->toToggle(),
            ModuleParameterEnum::ProjectProjects->toToggle(),
        ];
    }
}
