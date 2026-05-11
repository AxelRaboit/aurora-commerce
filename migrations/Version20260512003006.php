<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename every ModuleParameterEnum setting key from the legacy
 * `backend_<module>_<sub>` shape (with `_admin` for top-level backend toggles)
 * to the new `modules_<module>_<sub>` shape, with strict `_backend`/`_frontend`
 * symmetry on top-level toggles.
 *
 * Examples:
 *   backend_editorial_admin     → modules_editorial_backend
 *   backend_editorial_frontend  → modules_editorial_frontend
 *   backend_editorial_posts     → modules_editorial_posts
 *
 * Updates both the `core_settings` table (parameter keys) and the JSON
 * `disabled_modules` column of `core_users` (per-user mask).
 */
final class Version20260512003006 extends AbstractMigration
{
    private const array RENAMES = [
        'backend_general_admin' => 'modules_general_backend',
        'backend_platform_admin' => 'modules_platform_backend',
        'backend_editorial_admin' => 'modules_editorial_backend',
        'backend_crm_admin' => 'modules_crm_backend',
        'backend_erp_admin' => 'modules_erp_backend',
        'backend_ecommerce_admin' => 'modules_ecommerce_backend',
        'backend_ecommerce_frontend' => 'modules_ecommerce_frontend',
        'backend_photo_admin' => 'modules_photo_backend',
        'backend_photo_frontend' => 'modules_photo_frontend',
        'backend_billing_admin' => 'modules_billing_backend',
        'backend_ged_admin' => 'modules_ged_backend',
        'backend_project_admin' => 'modules_project_backend',
        'backend_planning_admin' => 'modules_planning_backend',
        'backend_hr_admin' => 'modules_hr_backend',
        'backend_vault_admin' => 'modules_vault_backend',
        'backend_pdfform_admin' => 'modules_pdfform_backend',
        'backend_general_dashboard' => 'modules_general_dashboard',
        'backend_platform_media' => 'modules_platform_media',
        'backend_platform_users' => 'modules_platform_users',
        'backend_platform_agencies' => 'modules_platform_agencies',
        'backend_platform_services' => 'modules_platform_services',
        'backend_platform_settings' => 'modules_platform_settings',
        'backend_platform_themes' => 'modules_platform_themes',
        'backend_billing_tiers' => 'modules_billing_tiers',
        'backend_billing_invoices' => 'modules_billing_invoices',
        'backend_billing_compliance' => 'modules_billing_compliance',
        'backend_crm_contacts' => 'modules_crm_contacts',
        'backend_crm_companies' => 'modules_crm_companies',
        'backend_crm_deals' => 'modules_crm_deals',
        'backend_ecommerce_listings' => 'modules_ecommerce_listings',
        'backend_ecommerce_orders' => 'modules_ecommerce_orders',
        'backend_editorial_frontend' => 'modules_editorial_frontend',
        'backend_editorial_posts' => 'modules_editorial_posts',
        'backend_editorial_menus' => 'modules_editorial_menus',
        'backend_editorial_post_types' => 'modules_editorial_post_types',
        'backend_editorial_taxonomies' => 'modules_editorial_taxonomies',
        'backend_editorial_comments' => 'modules_editorial_comments',
        'backend_editorial_forms' => 'modules_editorial_forms',
        'backend_editorial_sitemap' => 'modules_editorial_sitemap',
        'backend_ged_documents' => 'modules_ged_documents',
        'backend_ged_categories' => 'modules_ged_categories',
        'backend_erp_products' => 'modules_erp_products',
        'backend_hr_employees' => 'modules_hr_employees',
        'backend_photo_galleries' => 'modules_photo_galleries',
        'backend_planning_plannings' => 'modules_planning_plannings',
        'backend_project_projects' => 'modules_project_projects',
        'backend_vault_safe' => 'modules_vault_safe',
        'backend_vault_password_generator' => 'modules_vault_password_generator',
        'backend_pdfform_templates' => 'modules_pdfform_templates',
        'backend_pdfform_documents' => 'modules_pdfform_documents',
    ];

    public function getDescription(): string
    {
        return 'Rename module setting keys from backend_<module>_<sub> to modules_<module>_<sub> with _backend/_frontend symmetry';
    }

    public function up(Schema $schema): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->addSql(
                'UPDATE core_settings SET setting_key = :new WHERE setting_key = :old',
                ['new' => $new, 'old' => $old],
            );
            $this->addSql(
                'UPDATE core_users SET disabled_modules = REPLACE(disabled_modules::text, :old, :new)::jsonb WHERE disabled_modules::text LIKE :pattern',
                ['old' => '"'.$old.'"', 'new' => '"'.$new.'"', 'pattern' => '%"'.$old.'"%'],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->addSql(
                'UPDATE core_settings SET setting_key = :old WHERE setting_key = :new',
                ['new' => $new, 'old' => $old],
            );
            $this->addSql(
                'UPDATE core_users SET disabled_modules = REPLACE(disabled_modules::text, :new, :old)::jsonb WHERE disabled_modules::text LIKE :pattern',
                ['old' => '"'.$old.'"', 'new' => '"'.$new.'"', 'pattern' => '%"'.$new.'"%'],
            );
        }
    }
}
