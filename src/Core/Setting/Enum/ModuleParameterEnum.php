<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Enum;

enum ModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    public const MODULE = 'modules';

    // Top-level modules
    case EditorialEnabled = 'backend_editorial_admin';
    case CrmEnabled = 'backend_crm_admin';
    case ErpEnabled = 'backend_erp_admin';
    case EcommerceEnabled = 'backend_ecommerce_admin';
    case EcommerceShopEnabled = 'backend_ecommerce_front';
    case PhotoEnabled = 'photo_admin';
    case PhotoPublicEnabled = 'photo_front';
    case BillingEnabled = 'backend_billing_admin';
    case GedEnabled = 'backend_ged_admin';
    case ProjectEnabled = 'backend_project_admin';
    case PlanningEnabled = 'backend_planning_admin';
    case HrEnabled = 'backend_hr_admin';
    case VaultEnabled = 'backend_vault_admin';
    case PdfFormEnabled = 'backend_pdfform_admin';

    // Sub-modules — Billing
    case BillingTiersEnabled = 'backend_billing_tiers';
    case BillingInvoicesEnabled = 'backend_billing_invoices';
    case BillingComplianceEnabled = 'backend_billing_compliance';

    // Sub-modules — CRM
    case CrmContactsEnabled = 'backend_crm_contacts';
    case CrmCompaniesEnabled = 'backend_crm_companies';
    case CrmDealsEnabled = 'backend_crm_deals';

    // Sub-modules — Ecommerce
    case EcommerceListingsEnabled = 'backend_ecommerce_listings';
    case EcommerceOrdersEnabled = 'backend_ecommerce_orders';

    // Sub-modules — Editorial
    case EditorialPostsEnabled = 'backend_editorial_posts';
    case EditorialMenusEnabled = 'backend_editorial_menus';
    case EditorialPostTypesEnabled = 'backend_editorial_post_types';
    case EditorialTaxonomiesEnabled = 'backend_editorial_taxonomies';
    case EditorialCommentsEnabled = 'backend_editorial_comments';
    case EditorialFormsEnabled = 'backend_editorial_forms';
    case EditorialSitemapEnabled = 'backend_editorial_sitemap';

    // Sub-modules — GED
    case GedDocumentsEnabled = 'backend_ged_documents';
    case GedCategoriesEnabled = 'backend_ged_categories';

    // Sub-modules — ERP
    case ErpProductsEnabled = 'backend_erp_products';

    // Sub-modules — HR
    case HrEmployeesEnabled = 'backend_hr_employees';

    // Sub-modules — Photo
    case PhotoGalleriesEnabled = 'backend_photo_galleries';

    // Sub-modules — Planning
    case PlanningPlanningsEnabled = 'backend_planning_plannings';

    // Sub-modules — Project
    case ProjectProjectsEnabled = 'backend_project_projects';

    // Sub-modules — Vault
    case VaultSafeEnabled = 'backend_vault_safe';
    case VaultPasswordGeneratorEnabled = 'backend_vault_password_generator';

    // Sub-modules — PdfForm
    case PdfFormTemplatesEnabled = 'backend_pdfform_templates';
    case PdfFormDocumentsEnabled = 'backend_pdfform_documents';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::EditorialEnabled => 'backend.modules.editorial',
            self::CrmEnabled => 'backend.modules.crm',
            self::ErpEnabled => 'backend.modules.erp',
            self::EcommerceEnabled => 'backend.modules.ecommerce',
            self::EcommerceShopEnabled => 'backend.modules.ecommerce_shop',
            self::PhotoEnabled => 'backend.modules.photo',
            self::PhotoPublicEnabled => 'backend.modules.photo_public',
            self::BillingEnabled => 'backend.modules.billing',
            self::GedEnabled => 'backend.modules.ged',
            self::ProjectEnabled => 'backend.modules.project',
            self::PlanningEnabled => 'backend.modules.planning',
            self::HrEnabled => 'backend.modules.hr',
            self::VaultEnabled => 'backend.modules.vault',
            self::PdfFormEnabled => 'backend.modules.pdfform',
            self::PdfFormTemplatesEnabled => 'backend.nav.pdfform_templates',
            self::PdfFormDocumentsEnabled => 'backend.nav.pdfform_documents',
            self::BillingTiersEnabled => 'backend.nav.tiers',
            self::BillingInvoicesEnabled => 'backend.nav.invoices',
            self::BillingComplianceEnabled => 'backend.nav.ocr_import',
            self::CrmContactsEnabled => 'backend.nav.contacts',
            self::CrmCompaniesEnabled => 'backend.nav.companies',
            self::CrmDealsEnabled => 'backend.nav.deals',
            self::EcommerceListingsEnabled => 'backend.nav.listings',
            self::EcommerceOrdersEnabled => 'backend.nav.orders',
            self::EditorialPostsEnabled => 'backend.nav.posts',
            self::EditorialMenusEnabled => 'backend.nav.menus',
            self::EditorialPostTypesEnabled => 'backend.nav.postTypes',
            self::EditorialTaxonomiesEnabled => 'backend.nav.taxonomies',
            self::EditorialCommentsEnabled => 'backend.nav.comments',
            self::EditorialFormsEnabled => 'backend.nav.forms',
            self::EditorialSitemapEnabled => 'backend.nav.sitemap',
            self::GedDocumentsEnabled => 'backend.nav.documents',
            self::GedCategoriesEnabled => 'backend.nav.ged_categories',
            self::ErpProductsEnabled => 'backend.nav.products',
            self::HrEmployeesEnabled => 'backend.nav.employees',
            self::PhotoGalleriesEnabled => 'backend.nav.galleries',
            self::PlanningPlanningsEnabled => 'backend.nav.plannings',
            self::ProjectProjectsEnabled => 'backend.nav.projects',
            self::VaultSafeEnabled => 'backend.nav.vault',
            self::VaultPasswordGeneratorEnabled => 'backend.nav.password_generator',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::EditorialEnabled => 'backend.modules.editorial_description',
            self::CrmEnabled => 'backend.modules.crm_description',
            self::ErpEnabled => 'backend.modules.erp_description',
            self::EcommerceEnabled => 'backend.modules.ecommerce_description',
            self::EcommerceShopEnabled => 'backend.modules.ecommerce_shop_description',
            self::PhotoEnabled => 'backend.modules.photo_description',
            self::PhotoPublicEnabled => 'backend.modules.photo_public_description',
            self::BillingEnabled => 'backend.modules.billing_description',
            self::GedEnabled => 'backend.modules.ged_description',
            self::ProjectEnabled => 'backend.modules.project_description',
            self::PlanningEnabled => 'backend.modules.planning_description',
            self::HrEnabled => 'backend.modules.hr_description',
            self::VaultEnabled => 'backend.modules.vault_description',
            self::PdfFormEnabled => 'backend.modules.pdfform_description',
            self::PdfFormTemplatesEnabled => 'backend.nav.pdfform_templates_description',
            self::PdfFormDocumentsEnabled => 'backend.nav.pdfform_documents_description',
            self::BillingTiersEnabled => 'backend.nav.tiers_description',
            self::BillingInvoicesEnabled => 'backend.nav.invoices_description',
            self::BillingComplianceEnabled => 'backend.nav.ocr_import_description',
            self::CrmContactsEnabled => 'backend.nav.contacts_description',
            self::CrmCompaniesEnabled => 'backend.nav.companies_description',
            self::CrmDealsEnabled => 'backend.nav.deals_description',
            self::EcommerceListingsEnabled => 'backend.nav.listings_description',
            self::EcommerceOrdersEnabled => 'backend.nav.orders_description',
            self::EditorialPostsEnabled => 'backend.nav.posts_description',
            self::EditorialMenusEnabled => 'backend.nav.menus_description',
            self::EditorialPostTypesEnabled => 'backend.nav.postTypes_description',
            self::EditorialTaxonomiesEnabled => 'backend.nav.taxonomies_description',
            self::EditorialCommentsEnabled => 'backend.nav.comments_description',
            self::EditorialFormsEnabled => 'backend.nav.forms_description',
            self::EditorialSitemapEnabled => 'backend.nav.sitemap_description',
            self::GedDocumentsEnabled => 'backend.nav.documents_description',
            self::GedCategoriesEnabled => 'backend.nav.ged_categories_description',
            self::ErpProductsEnabled => 'backend.nav.products_description',
            self::HrEmployeesEnabled => 'backend.nav.employees_description',
            self::PhotoGalleriesEnabled => 'backend.nav.galleries_description',
            self::PlanningPlanningsEnabled => 'backend.nav.plannings_description',
            self::ProjectProjectsEnabled => 'backend.nav.projects_description',
            self::VaultSafeEnabled => 'backend.nav.vault_description',
            self::VaultPasswordGeneratorEnabled => 'backend.nav.password_generator_description',
        };
    }

    public function getDefaultValue(): string
    {
        return '1';
    }

    public function getType(): string
    {
        return 'bool';
    }

    public function getGroup(): string
    {
        return self::MODULE;
    }

    /**
     * Returns the parent ModuleParameterEnum case for sub-modules, null for top-level.
     */
    public function getParentCase(): ?self
    {
        return match ($this) {
            self::BillingTiersEnabled, self::BillingInvoicesEnabled, self::BillingComplianceEnabled => self::BillingEnabled,
            self::CrmContactsEnabled, self::CrmCompaniesEnabled, self::CrmDealsEnabled => self::CrmEnabled,
            self::EcommerceListingsEnabled, self::EcommerceOrdersEnabled => self::EcommerceEnabled,
            self::EditorialPostsEnabled, self::EditorialMenusEnabled, self::EditorialPostTypesEnabled,
            self::EditorialTaxonomiesEnabled, self::EditorialCommentsEnabled, self::EditorialFormsEnabled,
            self::EditorialSitemapEnabled => self::EditorialEnabled,
            self::GedDocumentsEnabled, self::GedCategoriesEnabled => self::GedEnabled,
            self::ErpProductsEnabled => self::ErpEnabled,
            self::HrEmployeesEnabled => self::HrEnabled,
            self::PhotoGalleriesEnabled => self::PhotoEnabled,
            self::PlanningPlanningsEnabled => self::PlanningEnabled,
            self::ProjectProjectsEnabled => self::ProjectEnabled,
            self::VaultSafeEnabled, self::VaultPasswordGeneratorEnabled => self::VaultEnabled,
            self::PdfFormTemplatesEnabled, self::PdfFormDocumentsEnabled => self::PdfFormEnabled,
            default => null,
        };
    }

    /**
     * Returns the key of the parameter that must be active before this one can be enabled.
     * Defines the full dependency graph (top-level inter-module + sub-module chains).
     */
    public function getCascadeRequires(): ?string
    {
        return match ($this) {
            // Top-level inter-module dependencies
            self::ErpEnabled => self::CrmEnabled->value,
            self::EcommerceEnabled, self::EcommerceShopEnabled => self::ErpEnabled->value,
            self::BillingEnabled => self::CrmEnabled->value,
            self::PhotoPublicEnabled => self::PhotoEnabled->value,
            // Billing sub-modules
            self::BillingTiersEnabled => self::BillingEnabled->value,
            self::BillingInvoicesEnabled => self::BillingTiersEnabled->value,
            self::BillingComplianceEnabled => self::BillingEnabled->value,
            // CRM sub-modules
            self::CrmContactsEnabled => self::CrmEnabled->value,
            self::CrmCompaniesEnabled => self::CrmEnabled->value,
            self::CrmDealsEnabled => self::CrmContactsEnabled->value,
            // Ecommerce sub-modules
            self::EcommerceListingsEnabled => self::EcommerceEnabled->value,
            self::EcommerceOrdersEnabled => self::EcommerceListingsEnabled->value,
            // Editorial sub-modules
            self::EditorialPostsEnabled => self::EditorialEnabled->value,
            self::EditorialMenusEnabled => self::EditorialEnabled->value,
            self::EditorialPostTypesEnabled => self::EditorialEnabled->value,
            self::EditorialTaxonomiesEnabled => self::EditorialPostTypesEnabled->value,
            self::EditorialCommentsEnabled => self::EditorialPostsEnabled->value,
            self::EditorialFormsEnabled => self::EditorialEnabled->value,
            self::EditorialSitemapEnabled => self::EditorialPostsEnabled->value,
            // GED sub-modules
            self::GedDocumentsEnabled => self::GedEnabled->value,
            self::GedCategoriesEnabled => self::GedEnabled->value,
            // ERP sub-modules
            self::ErpProductsEnabled => self::ErpEnabled->value,
            // HR sub-modules
            self::HrEmployeesEnabled => self::HrEnabled->value,
            // Photo sub-modules
            self::PhotoGalleriesEnabled => self::PhotoEnabled->value,
            // Planning sub-modules
            self::PlanningPlanningsEnabled => self::PlanningEnabled->value,
            // Project sub-modules
            self::ProjectProjectsEnabled => self::ProjectEnabled->value,
            // Vault sub-modules
            self::VaultSafeEnabled => self::VaultEnabled->value,
            self::VaultPasswordGeneratorEnabled => self::VaultEnabled->value,
            self::PdfFormTemplatesEnabled => self::PdfFormEnabled->value,
            self::PdfFormDocumentsEnabled => self::PdfFormEnabled->value,
            default => null,
        };
    }

    /**
     * All descendant parameter keys (direct + transitive) that must be forced to '0'
     * when this parameter is turned off. Covers both cascade-requires children
     * and direct sub-modules (via getParentCase).
     *
     * @return list<string>
     */
    public function getCascadeDisableTargets(): array
    {
        $targets = [];
        foreach (self::cases() as $case) {
            if ($case->getCascadeRequires() === $this->value || $case->getParentCase() === $this) {
                $targets[] = $case->value;
                foreach ($case->getCascadeDisableTargets() as $transitive) {
                    $targets[] = $transitive;
                }
            }
        }

        return array_values(array_unique($targets));
    }

    /**
     * Returns the module identifier for top-level enabled cases, null for sub-modules.
     */
    public function getModuleId(): ?string
    {
        return match ($this) {
            self::EditorialEnabled => 'editorial',
            self::CrmEnabled => 'crm',
            self::ErpEnabled => 'erp',
            self::EcommerceEnabled => 'ecommerce',
            self::PhotoEnabled => 'photo',
            self::BillingEnabled => 'billing',
            self::GedEnabled => 'ged',
            self::ProjectEnabled => 'project',
            self::PlanningEnabled => 'planning',
            self::HrEnabled => 'hr',
            self::VaultEnabled => 'vault',
            self::PdfFormEnabled => 'pdfform',
            default => null,
        };
    }
}
