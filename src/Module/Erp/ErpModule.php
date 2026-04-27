<?php

declare(strict_types=1);

namespace Aurora\Module\Erp;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\User\Enum\UserRoleEnum;
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
            new NavPermission('erp.products.view', UserRoleEnum::Editor->value),
            new NavPermission('erp.products.create', UserRoleEnum::Editor->value),
            new NavPermission('erp.products.edit', UserRoleEnum::Editor->value),
            new NavPermission('erp.products.delete', UserRoleEnum::Admin->value),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->erpContext->isAdminEnabled()) {
            return [];
        }

        return [
            new NavSection('erp', [
                new NavItem('erp_products', 'admin.nav.products', 'package', UserRoleEnum::Editor->value),
            ], priority: 50),
        ];
    }
}
