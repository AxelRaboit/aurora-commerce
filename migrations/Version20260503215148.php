<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503215148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_logs ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D62F2858AEA34913 ON audit_logs (reference)');
        $this->addSql('ALTER TABLE billing_ocr_jobs ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BB1514F3AEA34913 ON billing_ocr_jobs (reference)');
        $this->addSql('ALTER TABLE ecommerce_cart_items ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4C2D1439AEA34913 ON ecommerce_cart_items (reference)');
        $this->addSql('ALTER TABLE ecommerce_carts ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_49630C21AEA34913 ON ecommerce_carts (reference)');
        $this->addSql('ALTER TABLE ecommerce_order_lines ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_95DE1CECAEA34913 ON ecommerce_order_lines (reference)');
        $this->addSql('ALTER TABLE form_fields ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7C0B3726AEA34913 ON form_fields (reference)');
        $this->addSql('ALTER TABLE media_folders ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9FE05546AEA34913 ON media_folders (reference)');
        $this->addSql('ALTER TABLE menu_items ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70B2CA2AAEA34913 ON menu_items (reference)');
        $this->addSql('ALTER TABLE photo_gallery_finalizations ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A11B9099AEA34913 ON photo_gallery_finalizations (reference)');
        $this->addSql('ALTER TABLE photo_gallery_item_comments ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7F50871EAEA34913 ON photo_gallery_item_comments (reference)');
        $this->addSql('ALTER TABLE photo_gallery_picks ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1CE6B23CAEA34913 ON photo_gallery_picks (reference)');
        $this->addSql('ALTER TABLE reset_password_requests ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_16646B41AEA34913 ON reset_password_requests (reference)');
        $this->addSql('ALTER TABLE taxonomy_terms ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DDC5FBDCAEA34913 ON taxonomy_terms (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D62F2858AEA34913');
        $this->addSql('ALTER TABLE audit_logs DROP reference');
        $this->addSql('DROP INDEX UNIQ_BB1514F3AEA34913');
        $this->addSql('ALTER TABLE billing_ocr_jobs DROP reference');
        $this->addSql('DROP INDEX UNIQ_4C2D1439AEA34913');
        $this->addSql('ALTER TABLE ecommerce_cart_items DROP reference');
        $this->addSql('DROP INDEX UNIQ_49630C21AEA34913');
        $this->addSql('ALTER TABLE ecommerce_carts DROP reference');
        $this->addSql('DROP INDEX UNIQ_95DE1CECAEA34913');
        $this->addSql('ALTER TABLE ecommerce_order_lines DROP reference');
        $this->addSql('DROP INDEX UNIQ_7C0B3726AEA34913');
        $this->addSql('ALTER TABLE form_fields DROP reference');
        $this->addSql('DROP INDEX UNIQ_9FE05546AEA34913');
        $this->addSql('ALTER TABLE media_folders DROP reference');
        $this->addSql('DROP INDEX UNIQ_70B2CA2AAEA34913');
        $this->addSql('ALTER TABLE menu_items DROP reference');
        $this->addSql('DROP INDEX UNIQ_A11B9099AEA34913');
        $this->addSql('ALTER TABLE photo_gallery_finalizations DROP reference');
        $this->addSql('DROP INDEX UNIQ_7F50871EAEA34913');
        $this->addSql('ALTER TABLE photo_gallery_item_comments DROP reference');
        $this->addSql('DROP INDEX UNIQ_1CE6B23CAEA34913');
        $this->addSql('ALTER TABLE photo_gallery_picks DROP reference');
        $this->addSql('DROP INDEX UNIQ_16646B41AEA34913');
        $this->addSql('ALTER TABLE reset_password_requests DROP reference');
        $this->addSql('DROP INDEX UNIQ_DDC5FBDCAEA34913');
        $this->addSql('ALTER TABLE taxonomy_terms DROP reference');
    }
}
