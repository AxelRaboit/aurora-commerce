<?php

declare(strict_types=1);

namespace App\Module\Crm;

use App\Core\Module\ModuleInterface;
use App\Core\Module\NavItem;
use App\Core\Module\NavPermission;
use App\Core\Module\NavSection;
use App\Core\User\Enum\UserRoleEnum;
use App\Module\Crm\Service\CrmContext;

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
