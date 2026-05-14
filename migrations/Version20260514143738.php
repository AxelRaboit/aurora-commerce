<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514143738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE seq_core_listing_category_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_listing_category_translation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_ecommerce_listing_categories (position INT DEFAULT 0 NOT NULL, is_visible BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, parent_id INT DEFAULT NULL, image_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_3015D435727ACA70 ON core_ecommerce_listing_categories (parent_id)');
        $this->addSql('CREATE INDEX IDX_3015D4353DA5256D ON core_ecommerce_listing_categories (image_id)');
        $this->addSql('CREATE TABLE core_ecommerce_listing_category_translations (locale VARCHAR(10) NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL, seo_title VARCHAR(200) DEFAULT NULL, seo_description TEXT DEFAULT NULL, id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_B2F8D54B12469DE2 ON core_ecommerce_listing_category_translations (category_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_listing_category_translation_locale ON core_ecommerce_listing_category_translations (category_id, locale)');
        $this->addSql('CREATE UNIQUE INDEX uniq_listing_category_translation_slug ON core_ecommerce_listing_category_translations (locale, slug)');
        $this->addSql('ALTER TABLE core_ecommerce_listing_categories ADD CONSTRAINT FK_3015D435727ACA70 FOREIGN KEY (parent_id) REFERENCES core_ecommerce_listing_categories (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_ecommerce_listing_categories ADD CONSTRAINT FK_3015D4353DA5256D FOREIGN KEY (image_id) REFERENCES core_media (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_ecommerce_listing_category_translations ADD CONSTRAINT FK_B2F8D54B12469DE2 FOREIGN KEY (category_id) REFERENCES core_ecommerce_listing_categories (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_listing_category_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_listing_category_translation_id CASCADE');
        $this->addSql('ALTER TABLE core_ecommerce_listing_categories DROP CONSTRAINT FK_3015D435727ACA70');
        $this->addSql('ALTER TABLE core_ecommerce_listing_categories DROP CONSTRAINT FK_3015D4353DA5256D');
        $this->addSql('ALTER TABLE core_ecommerce_listing_category_translations DROP CONSTRAINT FK_B2F8D54B12469DE2');
        $this->addSql('DROP TABLE core_ecommerce_listing_categories');
        $this->addSql('DROP TABLE core_ecommerce_listing_category_translations');
    }
}
