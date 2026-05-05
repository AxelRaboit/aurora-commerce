<?php

declare(strict_types=1);

namespace Aurora\Module\Crm;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
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
            new NavPermission('crm.contacts.view'),
            new NavPermission('crm.contacts.create'),
            new NavPermission('crm.contacts.edit'),
            new NavPermission('crm.contacts.delete'),
            new NavPermission('crm.companies.manage'),
            new NavPermission('crm.deals.manage'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->crmContext->isAdminEnabled()) {
            return [];
        }

        return [
            new NavSection('crm', [
                new NavItem('crm_contacts', 'admin.nav.contacts', 'users'),
                new NavItem('crm_companies', 'admin.nav.companies', 'building-2'),
                new NavItem('crm_deals', 'admin.nav.deals', 'trending-up'),
            ], priority: 40),
        ];
    }
}
