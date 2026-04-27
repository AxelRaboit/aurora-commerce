<?php

declare(strict_types=1);

namespace Aurora\Module\Crm;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Module\Crm\Service\CrmContext;

final readonly class CrmModule implements ModuleInterface
{
    public function __construct(private CrmContext $crmContext) {}

    public function getId(): string
    {
        return 'crm';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('crm.contacts.view', UserRoleEnum::Editor->value),
            new NavPermission('crm.contacts.create', UserRoleEnum::Editor->value),
            new NavPermission('crm.contacts.edit', UserRoleEnum::Editor->value),
            new NavPermission('crm.contacts.delete', UserRoleEnum::Admin->value),
            new NavPermission('crm.companies.manage', UserRoleEnum::Editor->value),
            new NavPermission('crm.deals.manage', UserRoleEnum::Editor->value),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->crmContext->isAdminEnabled()) {
            return [];
        }

        return [
            new NavSection('crm', [
                new NavItem('crm_contacts', 'admin.nav.contacts', 'users', UserRoleEnum::Editor->value),
                new NavItem('crm_companies', 'admin.nav.companies', 'building-2', UserRoleEnum::Editor->value),
                new NavItem('crm_deals', 'admin.nav.deals', 'trending-up', UserRoleEnum::Editor->value),
            ], priority: 40),
        ];
    }
}
