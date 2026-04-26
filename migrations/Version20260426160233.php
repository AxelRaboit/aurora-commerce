<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426160233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE erp_products ADD image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE erp_products ADD CONSTRAINT FK_E050B8493DA5256D FOREIGN KEY (image_id) REFERENCES media (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_E050B8493DA5256D ON erp_products (image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE erp_products DROP CONSTRAINT FK_E050B8493DA5256D');
        $this->addSql('DROP INDEX IDX_E050B8493DA5256D');
        $this->addSql('ALTER TABLE erp_products DROP image_id');
    }
}
