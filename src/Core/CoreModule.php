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
            new NavPermission('core.dashboard.view'),
            new NavPermission('core.media.view'),
            new NavPermission('core.media.manage'),
            new NavPermission('core.menus.manage'),
            new NavPermission('core.search.view'),
            new NavPermission('core.users.manage'),
            new NavPermission('core.agencies.manage'),
            new NavPermission('core.services.manage'),
            new NavPermission('core.settings.manage'),
            new NavPermission('core.themes.manage'),
        ];
    }

    public function getNavSections(): array
    {
        return [
            new NavSection('core', [
                new NavItem('backend_dashboard', 'admin.nav.dashboard', 'layout-dashboard'),
            ], priority: 10),
            new NavSection('platform', [
                new NavItem('backend_media', 'admin.nav.media', 'image'),
                new NavItem('backend_menus', 'admin.nav.menus', 'menu'),
                new NavItem('backend_users', 'admin.nav.users', 'users'),
                new NavItem('backend_agencies', 'admin.nav.agencies', 'building-2'),
                new NavItem('backend_services', 'admin.nav.services', 'briefcase'),
                new NavItem('backend_settings', 'admin.nav.settings', 'settings'),
                new NavItem('backend_themes', 'admin.nav.themes', 'palette'),
            ], priority: 20),
            new NavSection('dev', [
                new NavItem('dev_dashboard', 'admin.nav.administration', 'shield', 'ROLE_DEV', 'rose', 'dev_'),
            ], priority: 1000),
        ];
    }
}
