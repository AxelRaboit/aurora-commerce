<?php

declare(strict_types=1);

namespace Aurora\Module\Vault;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class VaultModule implements ModuleInterface, ModuleToggleProviderInterface
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
            new NavPermission('vault.password_generator.use'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->vaultContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->vaultContext->isSafeEnabled()) {
            $items[] = new NavItem('backend_vault', 'backend.nav.vault', 'vault', requiredPrivilege: 'vault.use', descriptionKey: 'backend.nav.vault_description');
        }

        if ($this->vaultContext->isPasswordGeneratorEnabled()) {
            $items[] = new NavItem('backend_password_generator', 'backend.nav.password_generator', 'key-round', requiredPrivilege: 'vault.password_generator.use', descriptionKey: 'backend.nav.password_generator_description');
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
                new NavItem('backend_vault', 'backend.nav.vault', 'vault', requiredPrivilege: 'vault.use', descriptionKey: 'backend.nav.vault_description'),
                new NavItem('backend_password_generator', 'backend.nav.password_generator', 'key-round', requiredPrivilege: 'vault.password_generator.use', descriptionKey: 'backend.nav.password_generator_description'),
            ], priority: 20),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::VaultBackend->toToggle(),
            ModuleParameterEnum::VaultSafe->toToggle(),
            ModuleParameterEnum::VaultPasswordGenerator->toToggle(),
        ];
    }
}
