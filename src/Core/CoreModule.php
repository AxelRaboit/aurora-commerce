<?php

declare(strict_types=1);

namespace Aurora\Core;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\ModuleToggleProviderInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\Service\GeneralContext;
use Aurora\Core\Service\PlatformContext;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class CoreModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(
        private GeneralContext $generalContext,
        private PlatformContext $platformContext,
    ) {}

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
        $sections = [];

        if ($this->generalContext->isAdminEnabled()) {
            $generalItems = [];

            if ($this->generalContext->isDashboardEnabled()) {
                $generalItems[] = new NavItem('backend_dashboard', 'backend.nav.dashboard', 'layout-dashboard', descriptionKey: 'backend.nav.dashboard_description');
            }

            if ([] !== $generalItems) {
                $sections[] = new NavSection('core', $generalItems, priority: 10);
            }
        }

        if ($this->platformContext->isAdminEnabled()) {
            $platformItems = [];

            if ($this->platformContext->isMediaEnabled()) {
                $platformItems[] = new NavItem('backend_media', 'backend.nav.media', 'image', requiredPrivilege: 'core.media.view', descriptionKey: 'backend.nav.media_description');
            }

            if ($this->platformContext->isUsersEnabled()) {
                $platformItems[] = new NavItem('backend_users', 'backend.nav.users', 'users', requiredPrivilege: 'core.users.manage', descriptionKey: 'backend.nav.users_description');
            }

            if ($this->platformContext->isAgenciesEnabled()) {
                $platformItems[] = new NavItem('backend_agencies', 'backend.nav.agencies', 'building-2', requiredPrivilege: 'core.agencies.manage', descriptionKey: 'backend.nav.agencies_description');
            }

            if ($this->platformContext->isServicesEnabled()) {
                $platformItems[] = new NavItem('backend_services', 'backend.nav.services', 'briefcase', requiredPrivilege: 'core.services.manage', descriptionKey: 'backend.nav.services_description');
            }

            if ($this->platformContext->isSettingsEnabled()) {
                $platformItems[] = new NavItem('backend_settings', 'backend.nav.settings', 'settings', requiredPrivilege: 'core.settings.manage', descriptionKey: 'backend.nav.settings_description');
            }

            if ($this->platformContext->isThemesEnabled()) {
                $platformItems[] = new NavItem('backend_themes', 'backend.nav.themes', 'palette', requiredPrivilege: 'core.themes.manage', descriptionKey: 'backend.nav.themes_description');
            }

            if ([] !== $platformItems) {
                $sections[] = new NavSection('platform', $platformItems, priority: 20);
            }
        }

        $sections[] = new NavSection('dev', [
            new NavItem('dev_dashboard', 'backend.nav.administration', 'shield', 'ROLE_DEV', 'rose', 'dev_', descriptionKey: 'backend.nav.administration_description'),
        ], priority: 1000);

        return $sections;
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('core', [
                new NavItem('backend_dashboard', 'backend.nav.dashboard', 'layout-dashboard', descriptionKey: 'backend.nav.dashboard_description'),
            ], priority: 10),
            new NavSection('platform', [
                new NavItem('backend_media', 'backend.nav.media', 'image', requiredPrivilege: 'core.media.view', descriptionKey: 'backend.nav.media_description'),
                new NavItem('backend_users', 'backend.nav.users', 'users', requiredPrivilege: 'core.users.manage', descriptionKey: 'backend.nav.users_description'),
                new NavItem('backend_agencies', 'backend.nav.agencies', 'building-2', requiredPrivilege: 'core.agencies.manage', descriptionKey: 'backend.nav.agencies_description'),
                new NavItem('backend_services', 'backend.nav.services', 'briefcase', requiredPrivilege: 'core.services.manage', descriptionKey: 'backend.nav.services_description'),
                new NavItem('backend_settings', 'backend.nav.settings', 'settings', requiredPrivilege: 'core.settings.manage', descriptionKey: 'backend.nav.settings_description'),
                new NavItem('backend_themes', 'backend.nav.themes', 'palette', requiredPrivilege: 'core.themes.manage', descriptionKey: 'backend.nav.themes_description'),
            ], priority: 20),
            new NavSection('dev', [
                new NavItem('dev_dashboard', 'backend.nav.administration', 'shield', 'ROLE_DEV', 'rose', 'dev_', descriptionKey: 'backend.nav.administration_description'),
            ], priority: 1000),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::GeneralEnabled->toToggle(),
            ModuleParameterEnum::GeneralDashboardEnabled->toToggle(),
            ModuleParameterEnum::PlatformEnabled->toToggle(),
            ModuleParameterEnum::PlatformMediaEnabled->toToggle(),
            ModuleParameterEnum::PlatformUsersEnabled->toToggle(),
            ModuleParameterEnum::PlatformAgenciesEnabled->toToggle(),
            ModuleParameterEnum::PlatformServicesEnabled->toToggle(),
            ModuleParameterEnum::PlatformSettingsEnabled->toToggle(),
            ModuleParameterEnum::PlatformThemesEnabled->toToggle(),
        ];
    }
}
