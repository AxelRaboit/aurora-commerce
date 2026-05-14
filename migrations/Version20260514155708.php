<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514155708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE core_ecommerce_listing_tag_map (listing_id INT NOT NULL, listing_tag_id INT NOT NULL, PRIMARY KEY (listing_id, listing_tag_id))');
        $this->addSql('CREATE INDEX IDX_B1747780D4619D1A ON core_ecommerce_listing_tag_map (listing_id)');
        $this->addSql('CREATE INDEX IDX_B17477805E2A42C2 ON core_ecommerce_listing_tag_map (listing_tag_id)');
        $this->addSql('ALTER TABLE core_ecommerce_listing_tag_map ADD CONSTRAINT FK_B1747780D4619D1A FOREIGN KEY (listing_id) REFERENCES core_ecommerce_listings (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_ecommerce_listing_tag_map ADD CONSTRAINT FK_B17477805E2A42C2 FOREIGN KEY (listing_tag_id) REFERENCES core_ecommerce_listing_tags (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_ecommerce_listing_tag_map DROP CONSTRAINT FK_B1747780D4619D1A');
        $this->addSql('ALTER TABLE core_ecommerce_listing_tag_map DROP CONSTRAINT FK_B17477805E2A42C2');
        $this->addSql('DROP TABLE core_ecommerce_listing_tag_map');
    }
}
