<?php

declare(strict_types=1);

namespace Aurora\Module\Ged;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Ged\Service\GedContext;

final readonly class GedModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private GedContext $gedContext) {}

    public function getId(): string
    {
        return 'ged';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('ged.documents.view'),
            new NavPermission('ged.documents.create'),
            new NavPermission('ged.documents.edit'),
            new NavPermission('ged.documents.delete'),
            new NavPermission('ged.categories.view'),
            new NavPermission('ged.categories.create'),
            new NavPermission('ged.categories.edit'),
            new NavPermission('ged.categories.delete'),
            new NavPermission('ged.tags.manage'),
            new NavPermission('ged.folders.manage'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->gedContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->gedContext->isDocumentsEnabled()) {
            $items[] = new NavItem('backend_ged_documents', 'backend.nav.documents', 'folder-open', requiredPrivilege: 'ged.documents.view', descriptionKey: 'backend.nav.documents_description');
        }

        if ($this->gedContext->isCategoriesEnabled()) {
            $items[] = new NavItem('backend_ged_categories', 'backend.nav.ged_categories', 'tags', requiredPrivilege: 'ged.categories.view', descriptionKey: 'backend.nav.ged_categories_description');
        }

        if ($this->gedContext->isTagsEnabled()) {
            $items[] = new NavItem('backend_ged_tags', 'backend.nav.ged_tags', 'tag', requiredPrivilege: 'ged.tags.manage', descriptionKey: 'backend.nav.ged_tags_description');
        }

        if ($this->gedContext->isFoldersEnabled()) {
            $items[] = new NavItem('backend_ged_folders', 'backend.nav.ged_folders', 'folder', requiredPrivilege: 'ged.folders.manage', descriptionKey: 'backend.nav.ged_folders_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('ged', $items, priority: 35)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('ged', [
                new NavItem('backend_ged_documents', 'backend.nav.documents', 'folder-open', requiredPrivilege: 'ged.documents.view', descriptionKey: 'backend.nav.documents_description'),
                new NavItem('backend_ged_categories', 'backend.nav.ged_categories', 'tags', requiredPrivilege: 'ged.categories.view', descriptionKey: 'backend.nav.ged_categories_description'),
                new NavItem('backend_ged_tags', 'backend.nav.ged_tags', 'tag', requiredPrivilege: 'ged.tags.manage', descriptionKey: 'backend.nav.ged_tags_description'),
                new NavItem('backend_ged_folders', 'backend.nav.ged_folders', 'folder', requiredPrivilege: 'ged.folders.manage', descriptionKey: 'backend.nav.ged_folders_description'),
            ], priority: 35),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::GedBackend->toToggle(),
            ModuleParameterEnum::GedDocuments->toToggle(),
            ModuleParameterEnum::GedCategories->toToggle(),
            ModuleParameterEnum::GedTags->toToggle(),
            ModuleParameterEnum::GedFolders->toToggle(),
            ModuleParameterEnum::GedFrontend->toToggle(),
        ];
    }
}
