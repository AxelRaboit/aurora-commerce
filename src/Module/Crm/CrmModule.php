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

        return $this->getCatalogNavSections();
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('crm', [
                new NavItem('backend_crm_contacts', 'backend.nav.contacts', 'users', descriptionKey: 'backend.nav.contacts_description'),
                new NavItem('backend_crm_companies', 'backend.nav.companies', 'building-2', descriptionKey: 'backend.nav.companies_description'),
                new NavItem('backend_crm_deals', 'backend.nav.deals', 'trending-up', descriptionKey: 'backend.nav.deals_description'),
            ], priority: 40),
        ];
    }
}
