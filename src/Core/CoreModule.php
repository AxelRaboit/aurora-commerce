<?php

declare(strict_types=1);

namespace Aurora\Core;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;

final class CoreModule implements ModuleInterface
{
    public function getId(): string
    {
        return 'core';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('core.media.view'),
            new NavPermission('core.media.manage'),
            new NavPermission('core.search.view'),
            new NavPermission('core.users.manage'),
            new NavPermission('core.users.modules.manage'),
            new NavPermission('core.agencies.manage'),
            new NavPermission('core.services.manage'),
            new NavPermission('core.settings.manage'),
            new NavPermission('core.themes.manage'),
        ];
    }

    public function getNavSections(): array
    {
        return $this->getCatalogNavSections();
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('core', [
                new NavItem('backend_dashboard', 'backend.nav.dashboard', 'layout-dashboard', descriptionKey: 'backend.nav.dashboard_description'),
            ], priority: 10),
            new NavSection('platform', [
                new NavItem('backend_media', 'backend.nav.media', 'image', descriptionKey: 'backend.nav.media_description'),
                new NavItem('backend_users', 'backend.nav.users', 'users', descriptionKey: 'backend.nav.users_description'),
                new NavItem('backend_agencies', 'backend.nav.agencies', 'building-2', descriptionKey: 'backend.nav.agencies_description'),
                new NavItem('backend_services', 'backend.nav.services', 'briefcase', descriptionKey: 'backend.nav.services_description'),
                new NavItem('backend_settings', 'backend.nav.settings', 'settings', descriptionKey: 'backend.nav.settings_description'),
                new NavItem('backend_themes', 'backend.nav.themes', 'palette', descriptionKey: 'backend.nav.themes_description'),
            ], priority: 20),
            new NavSection('dev', [
                new NavItem('dev_dashboard', 'backend.nav.administration', 'shield', 'ROLE_DEV', 'rose', 'dev_', descriptionKey: 'backend.nav.administration_description'),
            ], priority: 1000),
        ];
    }
}
