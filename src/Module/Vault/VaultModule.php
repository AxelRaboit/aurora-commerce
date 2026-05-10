<?php

declare(strict_types=1);

namespace Aurora\Module\Vault;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Module\Vault\Service\VaultContext;

final readonly class VaultModule implements ModuleInterface
{
    public function __construct(private VaultContext $vaultContext) {}

    public function getId(): string
    {
        return 'vault';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('vault.use'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->vaultContext->isAdminEnabled()) {
            return [];
        }

        $items = [];

        if ($this->vaultContext->isSafeEnabled()) {
            $items[] = new NavItem('backend_vault', 'backend.nav.vault', 'vault', descriptionKey: 'backend.nav.vault_description');
        }

        if ($this->vaultContext->isPasswordGeneratorEnabled()) {
            $items[] = new NavItem('backend_password_generator', 'backend.nav.password_generator', 'key-round', requiredPrivilege: 'password_generator.use', descriptionKey: 'backend.nav.password_generator_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('vault', $items, priority: 20)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('vault', [
                new NavItem('backend_vault', 'backend.nav.vault', 'vault', descriptionKey: 'backend.nav.vault_description'),
                new NavItem('backend_password_generator', 'backend.nav.password_generator', 'key-round', requiredPrivilege: 'password_generator.use', descriptionKey: 'backend.nav.password_generator_description'),
            ], priority: 20),
        ];
    }
}
