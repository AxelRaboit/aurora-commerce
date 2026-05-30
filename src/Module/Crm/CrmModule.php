<?php

declare(strict_types=1);

namespace Aurora\Module\Crm;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Crm\Setting\CrmModuleParameterEnum;

final readonly class CrmModule implements ModuleInterface, ModuleToggleProviderInterface
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
            new NavPermission('crm.companies.view'),
            new NavPermission('crm.companies.create'),
            new NavPermission('crm.companies.edit'),
            new NavPermission('crm.companies.delete'),
            new NavPermission('crm.deals.view'),
            new NavPermission('crm.deals.create'),
            new NavPermission('crm.deals.edit'),
            new NavPermission('crm.deals.delete'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->crmContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->crmContext->isContactsEnabled()) {
            $items[] = new NavItem('backend_crm_contacts', 'backend.nav.contacts', 'users', requiredPrivilege: 'crm.contacts.view', descriptionKey: 'backend.nav.contacts_description');
            $items[] = new NavItem('backend_crm_contact_tags', 'backend.nav.contact_tags', 'tag', requiredPrivilege: 'crm.contacts.view', descriptionKey: 'backend.nav.contact_tags_description');
        }

        if ($this->crmContext->isCompaniesEnabled()) {
            $items[] = new NavItem('backend_crm_companies', 'backend.nav.companies', 'building-2', requiredPrivilege: 'crm.companies.view', descriptionKey: 'backend.nav.companies_description');
        }

        if ($this->crmContext->isDealsEnabled()) {
            $items[] = new NavItem('backend_crm_deals', 'backend.nav.deals', 'trending-up', requiredPrivilege: 'crm.deals.view', descriptionKey: 'backend.nav.deals_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('crm', $items, priority: 40)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('crm', [
                new NavItem('backend_crm_contacts', 'backend.nav.contacts', 'users', requiredPrivilege: 'crm.contacts.view', descriptionKey: 'backend.nav.contacts_description'),
                new NavItem('backend_crm_contact_tags', 'backend.nav.contact_tags', 'tag', requiredPrivilege: 'crm.contacts.view', descriptionKey: 'backend.nav.contact_tags_description'),
                new NavItem('backend_crm_companies', 'backend.nav.companies', 'building-2', requiredPrivilege: 'crm.companies.view', descriptionKey: 'backend.nav.companies_description'),
                new NavItem('backend_crm_deals', 'backend.nav.deals', 'trending-up', requiredPrivilege: 'crm.deals.view', descriptionKey: 'backend.nav.deals_description'),
            ], priority: 40),
        ];
    }

    public function getToggles(): array
    {
        return [
            CrmModuleParameterEnum::Backend->toToggle(),
            CrmModuleParameterEnum::Contacts->toToggle(),
            CrmModuleParameterEnum::Companies->toToggle(),
            CrmModuleParameterEnum::Deals->toToggle(),
        ];
    }
}
