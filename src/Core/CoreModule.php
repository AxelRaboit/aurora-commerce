<?php

declare(strict_types=1);

namespace Aurora\Core;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
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
            // Général
            new NavPermission('general.dashboard.view', group: 'general'),
            // Plateforme — declared by CoreModule but surfaced under the
            // "platform" section in the privileges modal via the group override.
            new NavPermission('core.media.view', group: 'platform'),
            new NavPermission('core.media.manage', group: 'platform'),
            new NavPermission('core.users.manage', group: 'platform'),
            new NavPermission('core.users.modules.manage', group: 'platform'),
            new NavPermission('core.agencies.manage', group: 'platform'),
            new NavPermission('core.services.manage', group: 'platform'),
            // Configuration — global app parameters & visual customization,
            // separated from operational data management above.
            new NavPermission('core.settings.manage', group: 'configuration'),
            new NavPermission('core.themes.manage', group: 'configuration'),
            // Cross-cutting (no specific UI section — left under module's own group)
            new NavPermission('core.search.view'),
        ];
    }

    public function getNavSections(): array
    {
        $sections = [];

        if ($this->generalContext->isBackendEnabled()) {
            $generalItems = [];

            if ($this->generalContext->isDashboardEnabled()) {
                $generalItems[] = new NavItem('backend_dashboard', 'backend.nav.dashboard', 'layout-dashboard', requiredPrivilege: 'general.dashboard.view', descriptionKey: 'backend.nav.dashboard_description');
            }

            if ([] !== $generalItems) {
                $sections[] = new NavSection('core', $generalItems, priority: 10);
            }
        }

        if ($this->platformContext->isBackendEnabled()) {
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

            if ([] !== $platformItems) {
                $sections[] = new NavSection('platform', $platformItems, priority: 20);
            }

            $configurationItems = [];

            if ($this->platformContext->isSettingsEnabled()) {
                $configurationItems[] = new NavItem('backend_settings', 'backend.nav.settings', 'settings', requiredPrivilege: 'core.settings.manage', descriptionKey: 'backend.nav.settings_description');
            }

            if ($this->platformContext->isThemesEnabled()) {
                $configurationItems[] = new NavItem('backend_themes', 'backend.nav.themes', 'palette', requiredPrivilege: 'core.themes.manage', descriptionKey: 'backend.nav.themes_description');
            }

            if ([] !== $configurationItems) {
                $sections[] = new NavSection('configuration', $configurationItems, priority: 25);
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
                new NavItem('backend_dashboard', 'backend.nav.dashboard', 'layout-dashboard', requiredPrivilege: 'general.dashboard.view', descriptionKey: 'backend.nav.dashboard_description'),
            ], priority: 10),
            new NavSection('platform', [
                new NavItem('backend_media', 'backend.nav.media', 'image', requiredPrivilege: 'core.media.view', descriptionKey: 'backend.nav.media_description'),
                new NavItem('backend_users', 'backend.nav.users', 'users', requiredPrivilege: 'core.users.manage', descriptionKey: 'backend.nav.users_description'),
                new NavItem('backend_agencies', 'backend.nav.agencies', 'building-2', requiredPrivilege: 'core.agencies.manage', descriptionKey: 'backend.nav.agencies_description'),
                new NavItem('backend_services', 'backend.nav.services', 'briefcase', requiredPrivilege: 'core.services.manage', descriptionKey: 'backend.nav.services_description'),
            ], priority: 20),
            new NavSection('configuration', [
                new NavItem('backend_settings', 'backend.nav.settings', 'settings', requiredPrivilege: 'core.settings.manage', descriptionKey: 'backend.nav.settings_description'),
                new NavItem('backend_themes', 'backend.nav.themes', 'palette', requiredPrivilege: 'core.themes.manage', descriptionKey: 'backend.nav.themes_description'),
            ], priority: 25),
            new NavSection('dev', [
                new NavItem('dev_dashboard', 'backend.nav.administration', 'shield', 'ROLE_DEV', 'rose', 'dev_', descriptionKey: 'backend.nav.administration_description'),
            ], priority: 1000),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::GeneralBackend->toToggle(),
            ModuleParameterEnum::GeneralDashboard->toToggle(),
            ModuleParameterEnum::PlatformBackend->toToggle(),
            ModuleParameterEnum::PlatformMedia->toToggle(),
            ModuleParameterEnum::PlatformUsers->toToggle(),
            ModuleParameterEnum::PlatformAgencies->toToggle(),
            ModuleParameterEnum::PlatformServices->toToggle(),
            ModuleParameterEnum::PlatformSettings->toToggle(),
            ModuleParameterEnum::PlatformThemes->toToggle(),
        ];
    }
}
