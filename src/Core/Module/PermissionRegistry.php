<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

final class PermissionRegistry
{
    /** @var array<string, string> permission name -> required role */
    private array $permissions = [];

    /** @var array<string, array<string, string>> module id -> (permission name -> required role) */
    private array $byModule = [];

    /** @param iterable<ModuleInterface> $modules */
    public function __construct(iterable $modules)
    {
        foreach ($modules as $module) {
            $moduleId = $module->getId();
            $this->byModule[$moduleId] ??= [];

            foreach ($module->getPermissions() as $permission) {
                $this->permissions[$permission->name] = $permission->requiredRole;
                $this->byModule[$moduleId][$permission->name] = $permission->requiredRole;
            }
        }
    }

    public function getRequiredRole(string $permissionName): ?string
    {
        return $this->permissions[$permissionName] ?? null;
    }

    public function has(string $permissionName): bool
    {
        return isset($this->permissions[$permissionName]);
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->permissions;
    }

    /** @return array<string, array<string, string>> */
    public function byModule(): array
    {
        return $this->byModule;
    }
}
