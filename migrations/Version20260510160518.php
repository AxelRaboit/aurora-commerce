<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260510160518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase all entity reference columns from VARCHAR(32) to VARCHAR(64)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_access_requests ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_audit_logs ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_billing_ocr_jobs ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_billing_tiers ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_comments ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_crm_companies ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_crm_contacts ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_crm_deals ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_ecommerce_cart_items ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_ecommerce_carts ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_ecommerce_listings ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_ecommerce_order_lines ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_form_fields ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_form_submissions ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_forms ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_ged_documents ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_media ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_media_folders ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_menu_items ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_photo_galleries ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_photo_gallery_finalizations ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_photo_gallery_invites ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_photo_gallery_item_comments ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_photo_gallery_items ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_photo_gallery_picks ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_posts ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_project_columns ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_project_tasks ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_projects ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_reset_password_requests ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_taxonomy_terms ALTER reference TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE core_users ALTER reference TYPE VARCHAR(64)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_access_requests ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_audit_logs ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_billing_ocr_jobs ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_billing_tiers ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_comments ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_crm_companies ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_crm_contacts ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_crm_deals ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_ecommerce_cart_items ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_ecommerce_carts ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_ecommerce_listings ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_ecommerce_order_lines ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_form_fields ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_form_submissions ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_forms ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_ged_documents ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_media ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_media_folders ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_menu_items ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_photo_galleries ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_photo_gallery_finalizations ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_photo_gallery_invites ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_photo_gallery_item_comments ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_photo_gallery_items ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_photo_gallery_picks ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_posts ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_project_columns ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_project_tasks ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_projects ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_reset_password_requests ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_taxonomy_terms ALTER reference TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE core_users ALTER reference TYPE VARCHAR(32)');
    }
}
