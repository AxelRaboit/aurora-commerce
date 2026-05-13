<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513215552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source enum + tags JSON to core_crm_contacts (CRM contact provenance + segmentation).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_crm_contacts ADD source VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE core_crm_contacts ADD tags JSON DEFAULT \'[]\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_crm_contacts DROP source');
        $this->addSql('ALTER TABLE core_crm_contacts DROP tags');
    }
}
