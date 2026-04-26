<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Module\ModuleInterface;
use App\Core\Module\NavItem;
use App\Core\Module\NavPermission;
use App\Core\Module\NavSection;
use App\Core\User\Enum\UserRoleEnum;

final class CoreModule implements ModuleInterface
{
    public function getId(): string
    {
        return 'core';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('core.dashboard.view', UserRoleEnum::Editor->value),
            new NavPermission('core.media.view', UserRoleEnum::Editor->value),
            new NavPermission('core.media.manage', UserRoleEnum::Admin->value),
            new NavPermission('core.menus.manage', UserRoleEnum::Admin->value),
            new NavPermission('core.search.view', UserRoleEnum::Editor->value),
            new NavPermission('core.users.manage', UserRoleEnum::Admin->value),
            new NavPermission('core.settings.manage', UserRoleEnum::Admin->value),
            new NavPermission('core.themes.manage', UserRoleEnum::Admin->value),
        ];
    }

    public function getNavSections(): array
    {
        return [
            new NavSection('core', [
                new NavItem('admin_dashboard', 'admin.nav.dashboard', 'layout-dashboard'),
            ], priority: 10),
            new NavSection('platform', [
                new NavItem('admin_media', 'admin.nav.media', 'image'),
                new NavItem('admin_menus', 'admin.nav.menus', 'menu'),
                new NavItem('admin_users', 'admin.nav.users', 'users', UserRoleEnum::Admin->value),
                new NavItem('admin_settings', 'admin.nav.settings', 'settings', UserRoleEnum::Admin->value),
                new NavItem('admin_themes', 'admin.nav.themes', 'palette', UserRoleEnum::Admin->value),
            ], priority: 20),
            new NavSection('dev', [
                new NavItem('dev_dashboard', 'admin.nav.administration', 'shield', UserRoleEnum::Dev->value, 'rose', 'dev_'),
            ], priority: 1000),
        ];
    }
}
