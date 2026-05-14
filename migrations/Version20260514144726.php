<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514144726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE core_ecommerce_listing_category_map (listing_id INT NOT NULL, listing_category_id INT NOT NULL, PRIMARY KEY (listing_id, listing_category_id))');
        $this->addSql('CREATE INDEX IDX_A77BDC76D4619D1A ON core_ecommerce_listing_category_map (listing_id)');
        $this->addSql('CREATE INDEX IDX_A77BDC76455844B0 ON core_ecommerce_listing_category_map (listing_category_id)');
        $this->addSql('ALTER TABLE core_ecommerce_listing_category_map ADD CONSTRAINT FK_A77BDC76D4619D1A FOREIGN KEY (listing_id) REFERENCES core_ecommerce_listings (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_ecommerce_listing_category_map ADD CONSTRAINT FK_A77BDC76455844B0 FOREIGN KEY (listing_category_id) REFERENCES core_ecommerce_listing_categories (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_ecommerce_listing_category_map DROP CONSTRAINT FK_A77BDC76D4619D1A');
        $this->addSql('ALTER TABLE core_ecommerce_listing_category_map DROP CONSTRAINT FK_A77BDC76455844B0');
        $this->addSql('DROP TABLE core_ecommerce_listing_category_map');
    }
}
