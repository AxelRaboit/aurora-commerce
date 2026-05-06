<?php

declare(strict_types=1);

namespace Aurora\Module\Ged;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Module\Ged\Service\GedContext;

final readonly class GedModule implements ModuleInterface
{
    public function __construct(private GedContext $gedContext) {}

    public function getId(): string
    {
        return 'ged';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('ged.documents.manage'),
            new NavPermission('ged.documents.delete'),
            new NavPermission('ged.categories.manage'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->gedContext->isAdminEnabled()) {
            return [];
        }

        return [
            new NavSection('ged', [
                new NavItem('backend_ged_documents', 'admin.nav.documents', 'folder-open'),
                new NavItem('backend_ged_categories', 'admin.nav.ged_categories', 'folder'),
            ], priority: 35),
        ];
    }
}
