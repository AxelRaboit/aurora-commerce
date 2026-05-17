<?php

declare(strict_types=1);

namespace Aurora\Module\Erp;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Erp\ErpContext;

final readonly class ErpModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private ErpContext $erpContext) {}

    public function getId(): string
    {
        return 'erp';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('erp.products.view'),
            new NavPermission('erp.products.create'),
            new NavPermission('erp.products.edit'),
            new NavPermission('erp.products.delete'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->erpContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->erpContext->isProductsEnabled()) {
            $items[] = new NavItem('backend_erp_products', 'backend.nav.products', 'package', requiredPrivilege: 'erp.products.view', descriptionKey: 'backend.nav.products_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('erp', $items, priority: 50)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('erp', [
                new NavItem('backend_erp_products', 'backend.nav.products', 'package', requiredPrivilege: 'erp.products.view', descriptionKey: 'backend.nav.products_description'),
            ], priority: 50),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::ErpBackend->toToggle(),
            ModuleParameterEnum::ErpProducts->toToggle(),
        ];
    }
}
