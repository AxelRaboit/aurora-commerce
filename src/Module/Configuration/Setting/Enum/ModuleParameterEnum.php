<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Setting\Enum;

use Aurora\Core\Module\Toggle\ModuleToggle;

enum ModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    public const MODULE = 'modules';

    // Top-level modules — backend (admin UI)
    case GeneralBackend = 'modules_general_backend';
    case PlatformBackend = 'modules_platform_backend';
    case ConfigurationBackend = 'modules_configuration_backend';
    case MediaBackend = 'modules_media_backend';
    case EditorialBackend = 'modules_editorial_backend';
    case CrmBackend = 'modules_crm_backend';
    case ErpBackend = 'modules_erp_backend';
    case EcommerceBackend = 'modules_ecommerce_backend';
    case PhotoBackend = 'modules_photo_backend';
    case BillingBackend = 'modules_billing_backend';
    case GedBackend = 'modules_ged_backend';
    case ProjectBackend = 'modules_project_backend';
    case PlanningBackend = 'modules_planning_backend';
    case HrBackend = 'modules_hr_backend';
    case VaultBackend = 'modules_vault_backend';
    case PdfFormBackend = 'modules_pdfform_backend';
    case NotesBackend = 'modules_notes_backend';
    case PersonalFinanceBackend = 'modules_personal_finance_backend';

    // Top-level modules — frontend (public site)
    case EcommerceFrontend = 'modules_ecommerce_frontend';
    case PhotoFrontend = 'modules_photo_frontend';
    case EditorialFrontend = 'modules_editorial_frontend';

    // Sub-modules — Core
    case GeneralDashboard = 'modules_general_dashboard';

    // Sub-modules — Platform
    case PlatformUsers = 'modules_platform_users';
    case PlatformAgencies = 'modules_platform_agencies';
    case PlatformServices = 'modules_platform_services';

    // Sub-modules — Configuration
    case ConfigurationSettings = 'modules_configuration_settings';
    case ConfigurationThemes = 'modules_configuration_themes';

    // Sub-modules — Media
    case MediaLibrary = 'modules_media_library';

    // Sub-modules — Billing
    case BillingTiers = 'modules_billing_tiers';
    case BillingInvoices = 'modules_billing_invoices';
    case BillingCompliance = 'modules_billing_compliance';

    // Sub-modules — CRM
    case CrmContacts = 'modules_crm_contacts';
    case CrmCompanies = 'modules_crm_companies';
    case CrmDeals = 'modules_crm_deals';

    // Sub-modules — Ecommerce
    case EcommerceListings = 'modules_ecommerce_listings';
    case EcommerceOrders = 'modules_ecommerce_orders';

    // Sub-modules — Editorial
    case EditorialPosts = 'modules_editorial_posts';
    case EditorialMenus = 'modules_editorial_menus';
    case EditorialPostTypes = 'modules_editorial_post_types';
    case EditorialTaxonomies = 'modules_editorial_taxonomies';
    case EditorialComments = 'modules_editorial_comments';
    case EditorialForms = 'modules_editorial_forms';
    case EditorialSitemap = 'modules_editorial_sitemap';

    // Sub-modules — GED
    case GedDocuments = 'modules_ged_documents';
    case GedCategories = 'modules_ged_categories';
    case GedTags = 'modules_ged_tags';
    case GedFolders = 'modules_ged_folders';
    case GedFrontend = 'modules_ged_frontend';

    // Sub-modules — ERP
    case ErpProducts = 'modules_erp_products';

    // Sub-modules — HR
    case HrEmployees = 'modules_hr_employees';

    // Sub-modules — Photo
    case PhotoGalleries = 'modules_photo_galleries';

    // Sub-modules — Planning
    case PlanningPlannings = 'modules_planning_plannings';

    // Sub-modules — Project
    case ProjectProjects = 'modules_project_projects';

    // Sub-modules — Vault
    case VaultSafe = 'modules_vault_safe';
    case VaultPasswordGenerator = 'modules_vault_password_generator';

    // Sub-modules — PdfForm
    case PdfFormTemplates = 'modules_pdfform_templates';
    case PdfFormDocuments = 'modules_pdfform_documents';

    // Sub-modules — Notes
    case NotesMarkdown = 'modules_notes_markdown';
    case NotesBlock = 'modules_notes_block';
    case NotesPostIt = 'modules_notes_post_it';

    // Sub-modules — PersonalFinance
    case PersonalFinanceWallets = 'modules_personal_finance_wallets';
    case PersonalFinanceCategories = 'modules_personal_finance_categories';
    case PersonalFinanceTransactions = 'modules_personal_finance_transactions';
    case PersonalFinanceBudgets = 'modules_personal_finance_budgets';
    case PersonalFinanceGoals = 'modules_personal_finance_goals';
    case PersonalFinanceRecurring = 'modules_personal_finance_recurring';
    case PersonalFinanceCategorization = 'modules_personal_finance_categorization';
    case PersonalFinanceOverview = 'modules_personal_finance_overview';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::GeneralBackend => 'backend.modules.general_backend',
            self::GeneralDashboard => 'backend.nav.dashboard',
            self::PlatformBackend => 'backend.modules.platform_backend',
            self::PlatformUsers => 'backend.nav.users',
            self::PlatformAgencies => 'backend.nav.agencies',
            self::PlatformServices => 'backend.nav.services',
            self::ConfigurationBackend => 'backend.modules.configuration',
            self::ConfigurationSettings => 'backend.nav.settings',
            self::ConfigurationThemes => 'backend.nav.themes',
            self::MediaBackend => 'backend.modules.media_backend',
            self::MediaLibrary => 'backend.nav.media',
            self::EditorialBackend => 'backend.modules.editorial_backend',
            self::CrmBackend => 'backend.modules.crm_backend',
            self::ErpBackend => 'backend.modules.erp_backend',
            self::EcommerceBackend => 'backend.modules.ecommerce_backend',
            self::EcommerceFrontend => 'backend.modules.ecommerce_frontend',
            self::PhotoBackend => 'backend.modules.photo_backend',
            self::PhotoFrontend => 'backend.modules.photo_frontend',
            self::BillingBackend => 'backend.modules.billing_backend',
            self::GedBackend => 'backend.modules.ged_backend',
            self::ProjectBackend => 'backend.modules.project_backend',
            self::PlanningBackend => 'backend.modules.planning_backend',
            self::HrBackend => 'backend.modules.hr_backend',
            self::VaultBackend => 'backend.modules.vault_backend',
            self::PdfFormBackend => 'backend.modules.pdfform_backend',
            self::PdfFormTemplates => 'backend.nav.pdfform_templates',
            self::PdfFormDocuments => 'backend.nav.pdfform_documents',
            self::BillingTiers => 'backend.nav.tiers',
            self::BillingInvoices => 'backend.nav.invoices',
            self::BillingCompliance => 'backend.nav.ocr_import',
            self::CrmContacts => 'backend.nav.contacts',
            self::CrmCompanies => 'backend.nav.companies',
            self::CrmDeals => 'backend.nav.deals',
            self::EcommerceListings => 'backend.nav.listings',
            self::EcommerceOrders => 'backend.nav.orders',
            self::EditorialFrontend => 'backend.modules.editorial_frontend',
            self::EditorialPosts => 'backend.nav.posts',
            self::EditorialMenus => 'backend.nav.menus',
            self::EditorialPostTypes => 'backend.nav.postTypes',
            self::EditorialTaxonomies => 'backend.nav.taxonomies',
            self::EditorialComments => 'backend.nav.comments',
            self::EditorialForms => 'backend.nav.forms',
            self::EditorialSitemap => 'backend.nav.sitemap',
            self::GedDocuments => 'backend.nav.documents',
            self::GedCategories => 'backend.nav.ged_categories',
            self::GedTags => 'backend.nav.ged_tags',
            self::GedFolders => 'backend.nav.ged_folders',
            self::GedFrontend => 'backend.modules.ged_frontend',
            self::ErpProducts => 'backend.nav.products',
            self::HrEmployees => 'backend.nav.employees',
            self::PhotoGalleries => 'backend.nav.galleries',
            self::PlanningPlannings => 'backend.nav.plannings',
            self::ProjectProjects => 'backend.nav.projects',
            self::VaultSafe => 'backend.nav.vault',
            self::VaultPasswordGenerator => 'backend.nav.password_generator',
            self::NotesBackend => 'backend.modules.notes_backend',
            self::NotesMarkdown => 'backend.nav.notes_markdown',
            self::NotesBlock => 'backend.nav.notes_block',
            self::NotesPostIt => 'backend.nav.notes_post_it',
            self::PersonalFinanceBackend => 'backend.modules.personal_finance_backend',
            self::PersonalFinanceWallets => 'backend.nav.personal_finance_wallets',
            self::PersonalFinanceCategories => 'backend.nav.personal_finance_categories',
            self::PersonalFinanceTransactions => 'backend.nav.personal_finance_transactions',
            self::PersonalFinanceBudgets => 'backend.nav.personal_finance_budgets',
            self::PersonalFinanceGoals => 'backend.nav.personal_finance_goals',
            self::PersonalFinanceRecurring => 'backend.nav.personal_finance_recurring',
            self::PersonalFinanceCategorization => 'backend.nav.personal_finance_categorization',
            self::PersonalFinanceOverview => 'backend.nav.personal_finance_overview',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::GeneralBackend => 'backend.modules.general_backend_description',
            self::GeneralDashboard => 'backend.nav.dashboard_description',
            self::PlatformBackend => 'backend.modules.platform_backend_description',
            self::PlatformUsers => 'backend.nav.users_description',
            self::PlatformAgencies => 'backend.nav.agencies_description',
            self::PlatformServices => 'backend.nav.services_description',
            self::ConfigurationBackend => 'backend.modules.configuration_description',
            self::ConfigurationSettings => 'backend.nav.settings_description',
            self::ConfigurationThemes => 'backend.nav.themes_description',
            self::MediaBackend => 'backend.modules.media_backend_description',
            self::MediaLibrary => 'backend.nav.media_description',
            self::EditorialBackend => 'backend.modules.editorial_backend_description',
            self::CrmBackend => 'backend.modules.crm_backend_description',
            self::ErpBackend => 'backend.modules.erp_backend_description',
            self::EcommerceBackend => 'backend.modules.ecommerce_backend_description',
            self::EcommerceFrontend => 'backend.modules.ecommerce_frontend_description',
            self::PhotoBackend => 'backend.modules.photo_backend_description',
            self::PhotoFrontend => 'backend.modules.photo_frontend_description',
            self::BillingBackend => 'backend.modules.billing_backend_description',
            self::GedBackend => 'backend.modules.ged_backend_description',
            self::ProjectBackend => 'backend.modules.project_backend_description',
            self::PlanningBackend => 'backend.modules.planning_backend_description',
            self::HrBackend => 'backend.modules.hr_backend_description',
            self::VaultBackend => 'backend.modules.vault_backend_description',
            self::PdfFormBackend => 'backend.modules.pdfform_backend_description',
            self::PdfFormTemplates => 'backend.nav.pdfform_templates_description',
            self::PdfFormDocuments => 'backend.nav.pdfform_documents_description',
            self::BillingTiers => 'backend.nav.tiers_description',
            self::BillingInvoices => 'backend.nav.invoices_description',
            self::BillingCompliance => 'backend.nav.ocr_import_description',
            self::CrmContacts => 'backend.nav.contacts_description',
            self::CrmCompanies => 'backend.nav.companies_description',
            self::CrmDeals => 'backend.nav.deals_description',
            self::EcommerceListings => 'backend.nav.listings_description',
            self::EcommerceOrders => 'backend.nav.orders_description',
            self::EditorialFrontend => 'backend.modules.editorial_frontend_description',
            self::EditorialPosts => 'backend.nav.posts_description',
            self::EditorialMenus => 'backend.nav.menus_description',
            self::EditorialPostTypes => 'backend.nav.postTypes_description',
            self::EditorialTaxonomies => 'backend.nav.taxonomies_description',
            self::EditorialComments => 'backend.nav.comments_description',
            self::EditorialForms => 'backend.nav.forms_description',
            self::EditorialSitemap => 'backend.nav.sitemap_description',
            self::GedDocuments => 'backend.nav.documents_description',
            self::GedCategories => 'backend.nav.ged_categories_description',
            self::GedTags => 'backend.nav.ged_tags_description',
            self::GedFolders => 'backend.nav.ged_folders_description',
            self::GedFrontend => 'backend.modules.ged_frontend_description',
            self::ErpProducts => 'backend.nav.products_description',
            self::HrEmployees => 'backend.nav.employees_description',
            self::PhotoGalleries => 'backend.nav.galleries_description',
            self::PlanningPlannings => 'backend.nav.plannings_description',
            self::ProjectProjects => 'backend.nav.projects_description',
            self::VaultSafe => 'backend.nav.vault_description',
            self::VaultPasswordGenerator => 'backend.nav.password_generator_description',
            self::NotesBackend => 'backend.modules.notes_backend_description',
            self::NotesMarkdown => 'backend.nav.notes_markdown_description',
            self::NotesBlock => 'backend.nav.notes_block_description',
            self::NotesPostIt => 'backend.nav.notes_post_it_description',
            self::PersonalFinanceBackend => 'backend.modules.personal_finance_backend_description',
            self::PersonalFinanceWallets => 'backend.nav.personal_finance_wallets_description',
            self::PersonalFinanceCategories => 'backend.nav.personal_finance_categories_description',
            self::PersonalFinanceTransactions => 'backend.nav.personal_finance_transactions_description',
            self::PersonalFinanceBudgets => 'backend.nav.personal_finance_budgets_description',
            self::PersonalFinanceGoals => 'backend.nav.personal_finance_goals_description',
            self::PersonalFinanceRecurring => 'backend.nav.personal_finance_recurring_description',
            self::PersonalFinanceCategorization => 'backend.nav.personal_finance_categorization_description',
            self::PersonalFinanceOverview => 'backend.nav.personal_finance_overview_description',
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
            self::GeneralDashboard => self::GeneralBackend,
            self::PlatformUsers, self::PlatformAgencies,
            self::PlatformServices => self::PlatformBackend,
            self::ConfigurationSettings, self::ConfigurationThemes => self::ConfigurationBackend,
            self::MediaLibrary => self::MediaBackend,
            self::BillingTiers, self::BillingInvoices, self::BillingCompliance => self::BillingBackend,
            self::CrmContacts, self::CrmCompanies, self::CrmDeals => self::CrmBackend,
            self::EcommerceListings, self::EcommerceOrders => self::EcommerceBackend,
            self::EditorialFrontend,
            self::EditorialPosts, self::EditorialMenus, self::EditorialPostTypes,
            self::EditorialTaxonomies, self::EditorialComments, self::EditorialForms,
            self::EditorialSitemap => self::EditorialBackend,
            self::GedDocuments, self::GedCategories, self::GedTags, self::GedFolders, self::GedFrontend => self::GedBackend,
            self::ErpProducts => self::ErpBackend,
            self::HrEmployees => self::HrBackend,
            self::PhotoGalleries => self::PhotoBackend,
            self::PlanningPlannings => self::PlanningBackend,
            self::ProjectProjects => self::ProjectBackend,
            self::VaultSafe, self::VaultPasswordGenerator => self::VaultBackend,
            self::PdfFormTemplates, self::PdfFormDocuments => self::PdfFormBackend,
            self::NotesMarkdown, self::NotesBlock, self::NotesPostIt => self::NotesBackend,
            self::PersonalFinanceWallets, self::PersonalFinanceCategories, self::PersonalFinanceTransactions, self::PersonalFinanceBudgets, self::PersonalFinanceGoals, self::PersonalFinanceRecurring, self::PersonalFinanceCategorization, self::PersonalFinanceOverview => self::PersonalFinanceBackend,
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
            self::ErpBackend => self::CrmBackend->value,
            self::EcommerceBackend, self::EcommerceFrontend => self::ErpBackend->value,
            self::BillingBackend => self::CrmBackend->value,
            self::PhotoFrontend => self::PhotoBackend->value,
            // Core sub-modules
            self::GeneralDashboard => self::GeneralBackend->value,
            // Platform sub-modules
            self::PlatformUsers,
            self::PlatformAgencies,
            self::PlatformServices => self::PlatformBackend->value,
            // Configuration sub-modules
            self::ConfigurationSettings,
            self::ConfigurationThemes => self::ConfigurationBackend->value,
            // Media sub-modules
            self::MediaLibrary => self::MediaBackend->value,
            // Billing sub-modules
            self::BillingTiers => self::BillingBackend->value,
            self::BillingInvoices => self::BillingTiers->value,
            self::BillingCompliance => self::BillingBackend->value,
            // CRM sub-modules
            self::CrmContacts => self::CrmBackend->value,
            self::CrmCompanies => self::CrmBackend->value,
            self::CrmDeals => self::CrmContacts->value,
            // Ecommerce sub-modules
            self::EcommerceListings => self::EcommerceBackend->value,
            self::EcommerceOrders => self::EcommerceListings->value,
            // Editorial sub-modules
            self::EditorialFrontend => self::EditorialBackend->value,
            self::EditorialPosts => self::EditorialBackend->value,
            self::EditorialMenus => self::EditorialBackend->value,
            self::EditorialPostTypes => self::EditorialBackend->value,
            self::EditorialTaxonomies => self::EditorialPostTypes->value,
            self::EditorialComments => self::EditorialPosts->value,
            self::EditorialForms => self::EditorialBackend->value,
            self::EditorialSitemap => self::EditorialPosts->value,
            // GED sub-modules
            self::GedDocuments => self::GedBackend->value,
            self::GedCategories => self::GedBackend->value,
            self::GedTags => self::GedBackend->value,
            self::GedFolders => self::GedBackend->value,
            self::GedFrontend => self::GedBackend->value,
            // ERP sub-modules
            self::ErpProducts => self::ErpBackend->value,
            // HR sub-modules
            self::HrEmployees => self::HrBackend->value,
            // Photo sub-modules
            self::PhotoGalleries => self::PhotoBackend->value,
            // Planning sub-modules
            self::PlanningPlannings => self::PlanningBackend->value,
            // Project sub-modules
            self::ProjectProjects => self::ProjectBackend->value,
            // Vault sub-modules
            self::VaultSafe => self::VaultBackend->value,
            self::VaultPasswordGenerator => self::VaultBackend->value,
            self::PdfFormTemplates => self::PdfFormBackend->value,
            self::PdfFormDocuments => self::PdfFormBackend->value,
            // Notes sub-modules
            self::NotesMarkdown => self::NotesBackend->value,
            self::NotesBlock => self::NotesBackend->value,
            self::NotesPostIt => self::NotesBackend->value,
            // PersonalFinance sub-modules
            self::PersonalFinanceWallets => self::PersonalFinanceBackend->value,
            self::PersonalFinanceCategories => self::PersonalFinanceWallets->value,
            self::PersonalFinanceTransactions => self::PersonalFinanceWallets->value,
            self::PersonalFinanceBudgets => self::PersonalFinanceTransactions->value,
            self::PersonalFinanceGoals => self::PersonalFinanceTransactions->value,
            self::PersonalFinanceRecurring => self::PersonalFinanceTransactions->value,
            self::PersonalFinanceCategorization => self::PersonalFinanceCategories->value,
            self::PersonalFinanceOverview => self::PersonalFinanceWallets->value,
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
     * Builds a {@see ModuleToggle} value object from this enum case so the
     * module can declare it via `ModuleToggleProviderInterface::getToggles()`.
     */
    public function toToggle(): ModuleToggle
    {
        return new ModuleToggle(
            key: $this->value,
            labelKey: $this->getLabel(),
            descriptionKey: $this->getDescription(),
            parentKey: $this->getCascadeRequires(),
            moduleId: $this->getModuleId(),
        );
    }

    /**
     * Returns the module identifier for top-level enabled cases, null for sub-modules.
     */
    public function getModuleId(): ?string
    {
        return match ($this) {
            self::GeneralBackend => 'general',
            self::PlatformBackend => 'platform',
            self::ConfigurationBackend => 'configuration',
            self::MediaBackend => 'media',
            self::EditorialBackend => 'editorial',
            self::CrmBackend => 'crm',
            self::ErpBackend => 'erp',
            self::EcommerceBackend => 'ecommerce',
            self::PhotoBackend => 'photo',
            self::BillingBackend => 'billing',
            self::GedBackend => 'ged',
            self::ProjectBackend => 'project',
            self::PlanningBackend => 'planning',
            self::HrBackend => 'hr',
            self::VaultBackend => 'vault',
            self::PdfFormBackend => 'pdfform',
            self::NotesBackend => 'notes',
            self::PersonalFinanceBackend => 'personal_finance',
            default => null,
        };
    }
}
