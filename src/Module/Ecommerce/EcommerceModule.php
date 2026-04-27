<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\User\Enum\UserRoleEnum;
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
            new NavPermission('ecommerce.listings.view', UserRoleEnum::Editor->value),
            new NavPermission('ecommerce.listings.create', UserRoleEnum::Editor->value),
            new NavPermission('ecommerce.listings.edit', UserRoleEnum::Editor->value),
            new NavPermission('ecommerce.listings.delete', UserRoleEnum::Admin->value),
            new NavPermission('ecommerce.orders.view', UserRoleEnum::Editor->value),
            new NavPermission('ecommerce.orders.manage', UserRoleEnum::Editor->value),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->ecommerceContext->isAdminEnabled()) {
            return [];
        }

        return [
            new NavSection('ecommerce', [
                new NavItem('ecommerce_listings', 'admin.nav.listings', 'shopping-bag', UserRoleEnum::Editor->value),
                new NavItem('ecommerce_orders', 'admin.nav.orders', 'receipt', UserRoleEnum::Editor->value),
            ], priority: 60),
        ];
    }
}
