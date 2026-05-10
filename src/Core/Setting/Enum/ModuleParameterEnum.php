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

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::EditorialEnabled => 'Module Editorial',
            self::CrmEnabled => 'Module CRM',
            self::ErpEnabled => 'Module ERP',
            self::EcommerceEnabled => 'Module E-commerce',
            self::EcommerceShopEnabled => 'Boutique publique',
            self::PhotoEnabled => 'Module Photo',
            self::PhotoPublicEnabled => 'Galeries publiques',
            self::BillingEnabled => 'Module Facturation',
            self::GedEnabled => 'Module GED',
            self::ProjectEnabled => 'Module Projet',
            self::PlanningEnabled => 'Module Planning',
            self::HrEnabled => 'Module RH',
            self::VaultEnabled => 'Module Outils',
            self::BillingTiersEnabled => 'Tiers',
            self::BillingInvoicesEnabled => 'Factures',
            self::BillingComplianceEnabled => 'Conformité',
            self::CrmContactsEnabled => 'Contacts',
            self::CrmCompaniesEnabled => 'Entreprises',
            self::CrmDealsEnabled => 'Affaires',
            self::EcommerceListingsEnabled => 'Produits boutique',
            self::EcommerceOrdersEnabled => 'Commandes',
            self::EditorialPostsEnabled => 'Articles',
            self::EditorialMenusEnabled => 'Menus',
            self::EditorialPostTypesEnabled => 'Types de contenu',
            self::EditorialTaxonomiesEnabled => 'Taxonomies',
            self::EditorialCommentsEnabled => 'Commentaires',
            self::EditorialFormsEnabled => 'Formulaires',
            self::EditorialSitemapEnabled => 'Sitemap',
            self::GedDocumentsEnabled => 'Documents',
            self::GedCategoriesEnabled => 'Catégories',
            self::ErpProductsEnabled => 'Produits',
            self::HrEmployeesEnabled => 'Employés',
            self::PhotoGalleriesEnabled => 'Galeries',
            self::PlanningPlanningsEnabled => 'Plannings',
            self::ProjectProjectsEnabled => 'Projets',
            self::VaultSafeEnabled => 'Coffre-fort',
            self::VaultPasswordGeneratorEnabled => 'Générateur de mots de passe',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::EditorialEnabled => "Active la section Editorial dans l'administration (articles, menus, formulaires, sitemap).",
            self::CrmEnabled => "Active la section CRM dans l'administration (contacts, entreprises, affaires).",
            self::ErpEnabled => "Active la section ERP dans l'administration (produits). Nécessite le CRM.",
            self::EcommerceEnabled => "Active la section E-commerce dans l'administration (listings, commandes). Nécessite l'ERP.",
            self::EcommerceShopEnabled => 'Active la boutique côté site public (catalogue, panier, checkout). Nécessite le module E-commerce.',
            self::PhotoEnabled => "Active la section Photographie dans l'administration (galeries de livraison client).",
            self::PhotoPublicEnabled => 'Active les galeries publiques côté front. Nécessite le module Photo.',
            self::BillingEnabled => "Active la section Facturation dans l'administration. Nécessite le CRM.",
            self::GedEnabled => "Active la section GED dans l'administration.",
            self::ProjectEnabled => "Active la section Projet dans l'administration.",
            self::PlanningEnabled => "Active la section Planning dans l'administration.",
            self::HrEnabled => "Active la section RH dans l'administration.",
            self::VaultEnabled => 'Active les outils utilitaires.',
            self::BillingTiersEnabled => 'Active la gestion des tiers (fournisseurs et clients) dans la facturation.',
            self::BillingInvoicesEnabled => 'Active la gestion des factures. Nécessite les tiers.',
            self::BillingComplianceEnabled => 'Active les outils de conformité (OCR, import).',
            self::CrmContactsEnabled => 'Active la gestion des contacts dans le CRM.',
            self::CrmCompaniesEnabled => 'Active la gestion des entreprises dans le CRM.',
            self::CrmDealsEnabled => 'Active la gestion des affaires (pipeline commercial). Nécessite les contacts.',
            self::EcommerceListingsEnabled => 'Active la gestion des produits boutique.',
            self::EcommerceOrdersEnabled => 'Active la gestion des commandes. Nécessite les produits boutique.',
            self::EditorialPostsEnabled => 'Active la gestion des articles.',
            self::EditorialMenusEnabled => 'Active la gestion des menus de navigation.',
            self::EditorialPostTypesEnabled => 'Active la gestion des types de contenu personnalisés.',
            self::EditorialTaxonomiesEnabled => 'Active la gestion des taxonomies. Nécessite les types de contenu.',
            self::EditorialCommentsEnabled => 'Active la gestion des commentaires. Nécessite les articles.',
            self::EditorialFormsEnabled => 'Active la gestion des formulaires.',
            self::EditorialSitemapEnabled => 'Active la génération et la gestion du sitemap. Nécessite les articles.',
            self::GedDocumentsEnabled => 'Active la gestion des documents GED.',
            self::GedCategoriesEnabled => 'Active la gestion des catégories de documents GED.',
            self::ErpProductsEnabled => 'Active la gestion des produits ERP.',
            self::HrEmployeesEnabled => 'Active la gestion des employés.',
            self::PhotoGalleriesEnabled => 'Active la gestion des galeries photo.',
            self::PlanningPlanningsEnabled => 'Active la gestion des plannings et événements.',
            self::ProjectProjectsEnabled => 'Active la gestion des projets et tâches.',
            self::VaultSafeEnabled => 'Active le coffre-fort de mots de passe chiffrés (E2E).',
            self::VaultPasswordGeneratorEnabled => 'Active le générateur de mots de passe.',
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
            default => null,
        };
    }
}
