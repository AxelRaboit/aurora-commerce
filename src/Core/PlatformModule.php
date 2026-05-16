<?php

declare(strict_types=1);

namespace Aurora\Core;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Service\PlatformContext;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

/**
 * Platform section — the organization layer of the backend (Users, Agencies,
 * Services). Media moved to {@see MediaModule} in Jalon 4.5 (cross-cutting
 * infra), Configuration (Settings, Themes) lives in {@see ConfigurationModule}
 * (admin params) — this class now owns the "who works for whom doing what"
 * triplet.
 *
 * Search (cross-cutting privilege `core.search.view`) lives here as a
 * convenient home — it isn't surfaced as a NavItem, but the permission
 * is declared so it shows up in the privileges modal's "platform" group.
 */
final readonly class PlatformModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private PlatformContext $platformContext) {}

    public function getId(): string
    {
        return 'platform';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('core.users.manage'),
            new NavPermission('core.users.modules.manage'),
            new NavPermission('core.agencies.manage'),
            new NavPermission('core.services.manage'),
            // Cross-cutting (no specific UI section — surfaced under platform).
            new NavPermission('core.search.view'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->platformContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->platformContext->isUsersEnabled()) {
            $items[] = new NavItem('backend_users', 'backend.nav.users', 'users', requiredPrivilege: 'core.users.manage', descriptionKey: 'backend.nav.users_description');
        }

        if ($this->platformContext->isAgenciesEnabled()) {
            $items[] = new NavItem('backend_agencies', 'backend.nav.agencies', 'building-2', requiredPrivilege: 'core.agencies.manage', descriptionKey: 'backend.nav.agencies_description');
        }

        if ($this->platformContext->isServicesEnabled()) {
            $items[] = new NavItem('backend_services', 'backend.nav.services', 'briefcase', requiredPrivilege: 'core.services.manage', descriptionKey: 'backend.nav.services_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('platform', $items, priority: 20)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('platform', [
                new NavItem('backend_users', 'backend.nav.users', 'users', requiredPrivilege: 'core.users.manage', descriptionKey: 'backend.nav.users_description'),
                new NavItem('backend_agencies', 'backend.nav.agencies', 'building-2', requiredPrivilege: 'core.agencies.manage', descriptionKey: 'backend.nav.agencies_description'),
                new NavItem('backend_services', 'backend.nav.services', 'briefcase', requiredPrivilege: 'core.services.manage', descriptionKey: 'backend.nav.services_description'),
            ], priority: 20),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::PlatformBackend->toToggle(),
            ModuleParameterEnum::PlatformUsers->toToggle(),
            ModuleParameterEnum::PlatformAgencies->toToggle(),
            ModuleParameterEnum::PlatformServices->toToggle(),
        ];
    }
}
