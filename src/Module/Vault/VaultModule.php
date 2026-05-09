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

        return $this->getCatalogNavSections();
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('vault', [
                new NavItem('backend_vault', 'backend.nav.vault', 'vault', descriptionKey: 'backend.nav.vault_description'),
            ], priority: 20),
        ];
    }
}
