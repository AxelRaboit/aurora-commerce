<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\ModuleToggleProviderInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Ecommerce\Service\EcommerceContext;

final readonly class EcommerceModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private EcommerceContext $ecommerceContext) {}

    public function getId(): string
    {
        return 'ecommerce';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('ecommerce.listings.view'),
            new NavPermission('ecommerce.listings.create'),
            new NavPermission('ecommerce.listings.edit'),
            new NavPermission('ecommerce.listings.delete'),
            new NavPermission('ecommerce.orders.view'),
            new NavPermission('ecommerce.orders.manage'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->ecommerceContext->isAdminEnabled()) {
            return [];
        }

        $items = [];

        if ($this->ecommerceContext->isListingsEnabled()) {
            $items[] = new NavItem('backend_ecommerce_listings', 'backend.nav.listings', 'shopping-bag', requiredPrivilege: 'ecommerce.listings.view', descriptionKey: 'backend.nav.listings_description');
        }

        if ($this->ecommerceContext->isOrdersEnabled()) {
            $items[] = new NavItem('backend_ecommerce_orders', 'backend.nav.orders', 'receipt', requiredPrivilege: 'ecommerce.orders.view', descriptionKey: 'backend.nav.orders_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('ecommerce', $items, priority: 60)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('ecommerce', [
                new NavItem('backend_ecommerce_listings', 'backend.nav.listings', 'shopping-bag', requiredPrivilege: 'ecommerce.listings.view', descriptionKey: 'backend.nav.listings_description'),
                new NavItem('backend_ecommerce_orders', 'backend.nav.orders', 'receipt', requiredPrivilege: 'ecommerce.orders.view', descriptionKey: 'backend.nav.orders_description'),
            ], priority: 60),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::EcommerceEnabled->toToggle(),
            ModuleParameterEnum::EcommerceShopEnabled->toToggle(),
            ModuleParameterEnum::EcommerceListingsEnabled->toToggle(),
            ModuleParameterEnum::EcommerceOrdersEnabled->toToggle(),
        ];
    }
}
