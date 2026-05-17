<?php

declare(strict_types=1);

namespace Aurora\Module\Platform;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Platform\PlatformContext;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

/**
 * Platform section — the organization layer of the backend (Users, Agencies,
 * Services). Media moved to {@see MediaModule} in Jalon 4.5 (cross-cutting
 * infra), Configuration (Settings, Themes) lives in {@see ConfigurationModule}
 * (admin params), and global search moved to {@see GeneralModule} in
 * Jalon 5.1 (it's a header feature, not a Platform-specific concern) —
 * this class now strictly owns the "who works for whom doing what" triplet.
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
            new NavPermission('platform.users.manage'),
            new NavPermission('platform.users.module_access.manage'),
            new NavPermission('platform.agencies.manage'),
            new NavPermission('platform.services.manage'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->platformContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->platformContext->isUsersEnabled()) {
            $items[] = new NavItem('backend_users', 'backend.nav.users', 'users', requiredPrivilege: 'platform.users.manage', descriptionKey: 'backend.nav.users_description');
        }

        if ($this->platformContext->isAgenciesEnabled()) {
            $items[] = new NavItem('backend_agencies', 'backend.nav.agencies', 'building-2', requiredPrivilege: 'platform.agencies.manage', descriptionKey: 'backend.nav.agencies_description');
        }

        if ($this->platformContext->isServicesEnabled()) {
            $items[] = new NavItem('backend_services', 'backend.nav.services', 'briefcase', requiredPrivilege: 'platform.services.manage', descriptionKey: 'backend.nav.services_description');
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
                new NavItem('backend_users', 'backend.nav.users', 'users', requiredPrivilege: 'platform.users.manage', descriptionKey: 'backend.nav.users_description'),
                new NavItem('backend_agencies', 'backend.nav.agencies', 'building-2', requiredPrivilege: 'platform.agencies.manage', descriptionKey: 'backend.nav.agencies_description'),
                new NavItem('backend_services', 'backend.nav.services', 'briefcase', requiredPrivilege: 'platform.services.manage', descriptionKey: 'backend.nav.services_description'),
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
