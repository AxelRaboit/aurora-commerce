<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Module\Ecommerce\Service\EcommerceContext;

final readonly class EcommerceModule implements ModuleInterface
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

        return [
            new NavSection('ecommerce', [
                new NavItem('ecommerce_listings', 'admin.nav.listings', 'shopping-bag'),
                new NavItem('ecommerce_orders', 'admin.nav.orders', 'receipt'),
            ], priority: 60),
        ];
    }
}
