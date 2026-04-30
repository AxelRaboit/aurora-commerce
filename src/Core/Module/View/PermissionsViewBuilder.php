<?php

declare(strict_types=1);

namespace Aurora\Core\Module\View;

use Aurora\Core\Module\PermissionRegistry;

/**
 * Builds the Twig payload for the dev permissions dashboard tab. Wraps the
 * registry traversal so the controller stays focused on flow (XHR vs page).
 */
final readonly class PermissionsViewBuilder
{
    public function __construct(private PermissionRegistry $permissionRegistry) {}

    /**
     * @return array<string, mixed>
     */
    public function permissionsPayload(): array
    {
        $modules = [];
        foreach ($this->permissionRegistry->byModule() as $moduleId => $permissions) {
            $items = [];
            foreach ($permissions as $name => $role) {
                $items[] = ['name' => $name, 'role' => $role];
            }

            $modules[] = ['id' => $moduleId, 'permissions' => $items];
        }

        return ['modules' => $modules];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $payload): array
    {
        return [
            'tab' => 'permissions',
            'permissions' => $payload,
        ];
    }
}
