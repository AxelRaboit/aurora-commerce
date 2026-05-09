<?php

declare(strict_types=1);

namespace Aurora\Module\Project;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Module\Project\Service\ProjectContext;

final readonly class ProjectModule implements ModuleInterface
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
        if (!$this->projectContext->isAdminEnabled()) {
            return [];
        }

        return $this->getCatalogNavSections();
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('project', [
                new NavItem('backend_projects', 'backend.nav.projects', 'folder-kanban', descriptionKey: 'backend.nav.projects_description'),
            ], priority: 35),
        ];
    }
}
