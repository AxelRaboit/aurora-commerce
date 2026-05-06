<?php

declare(strict_types=1);

namespace Aurora\Module\Erp;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Module\Erp\Service\ErpContext;

final readonly class ErpModule implements ModuleInterface
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
        if (!$this->erpContext->isAdminEnabled()) {
            return [];
        }

        return [
            new NavSection('erp', [
                new NavItem('backend_erp_products', 'backend.nav.products', 'package'),
            ], priority: 50),
        ];
    }
}
