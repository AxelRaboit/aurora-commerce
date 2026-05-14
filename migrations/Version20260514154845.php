<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514154845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE seq_core_listing_tag_translation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_listing_tag_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_ecommerce_listing_tag_translations (locale VARCHAR(10) NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(150) NOT NULL, description TEXT DEFAULT NULL, id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_83F405EEBAD26311 ON core_ecommerce_listing_tag_translations (tag_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_listing_tag_translation_locale ON core_ecommerce_listing_tag_translations (tag_id, locale)');
        $this->addSql('CREATE UNIQUE INDEX uniq_listing_tag_translation_slug ON core_ecommerce_listing_tag_translations (locale, slug)');
        $this->addSql('CREATE TABLE core_ecommerce_listing_tags (color VARCHAR(7) DEFAULT \'#6366F1\' NOT NULL, is_visible BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE core_ecommerce_listing_tag_translations ADD CONSTRAINT FK_83F405EEBAD26311 FOREIGN KEY (tag_id) REFERENCES core_ecommerce_listing_tags (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_listing_tag_translation_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_listing_tag_id CASCADE');
        $this->addSql('ALTER TABLE core_ecommerce_listing_tag_translations DROP CONSTRAINT FK_83F405EEBAD26311');
        $this->addSql('DROP TABLE core_ecommerce_listing_tag_translations');
        $this->addSql('DROP TABLE core_ecommerce_listing_tags');
    }
}
