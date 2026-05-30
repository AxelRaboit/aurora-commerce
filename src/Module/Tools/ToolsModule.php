<?php

declare(strict_types=1);

namespace Aurora\Module\Tools;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Tools\Setting\ToolsModuleParameterEnum;

final readonly class ToolsModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private ToolsContext $toolsContext) {}

    public function getId(): string
    {
        return 'tools';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('tools.vault.use'),
            new NavPermission('tools.password_generator.use'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->toolsContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->toolsContext->isVaultEnabled()) {
            $items[] = new NavItem('backend_tools_vault', 'backend.nav.vault', 'vault', requiredPrivilege: 'tools.vault.use', descriptionKey: 'backend.nav.vault_description');
        }

        if ($this->toolsContext->isPasswordGeneratorEnabled()) {
            $items[] = new NavItem('backend_tools_password_generator', 'backend.nav.password_generator', 'key-round', requiredPrivilege: 'tools.password_generator.use', descriptionKey: 'backend.nav.password_generator_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('tools', $items, priority: 20)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('tools', [
                new NavItem('backend_tools_vault', 'backend.nav.vault', 'vault', requiredPrivilege: 'tools.vault.use', descriptionKey: 'backend.nav.vault_description'),
                new NavItem('backend_tools_password_generator', 'backend.nav.password_generator', 'key-round', requiredPrivilege: 'tools.password_generator.use', descriptionKey: 'backend.nav.password_generator_description'),
            ], priority: 20),
        ];
    }

    public function getToggles(): array
    {
        return [
            ToolsModuleParameterEnum::Backend->toToggle(),
            ToolsModuleParameterEnum::Vault->toToggle(),
            ToolsModuleParameterEnum::PasswordGenerator->toToggle(),
        ];
    }
}
