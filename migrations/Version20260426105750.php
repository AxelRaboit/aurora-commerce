<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426105750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crm_contacts ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE crm_contacts DROP company');
        $this->addSql('ALTER TABLE crm_contacts ADD CONSTRAINT FK_5DC758D8979B1AD6 FOREIGN KEY (company_id) REFERENCES crm_companies (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_5DC758D8979B1AD6 ON crm_contacts (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crm_contacts DROP CONSTRAINT FK_5DC758D8979B1AD6');
        $this->addSql('DROP INDEX IDX_5DC758D8979B1AD6');
        $this->addSql('ALTER TABLE crm_contacts ADD company VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE crm_contacts DROP company_id');
    }
}
