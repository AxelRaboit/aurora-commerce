<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507191733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Project ↔ CrmContact: replace single FK with many-to-many join table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE core_project_crm_contacts (project_id INT NOT NULL, contact_id INT NOT NULL, PRIMARY KEY (project_id, contact_id))');
        $this->addSql('CREATE INDEX IDX_PROJECT_CRM_CONTACTS_PROJECT ON core_project_crm_contacts (project_id)');
        $this->addSql('CREATE INDEX IDX_PROJECT_CRM_CONTACTS_CONTACT ON core_project_crm_contacts (contact_id)');
        $this->addSql('ALTER TABLE core_project_crm_contacts ADD CONSTRAINT FK_PROJECT_CRM_CONTACTS_PROJECT FOREIGN KEY (project_id) REFERENCES core_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_crm_contacts ADD CONSTRAINT FK_PROJECT_CRM_CONTACTS_CONTACT FOREIGN KEY (contact_id) REFERENCES core_crm_contacts (id) ON DELETE CASCADE NOT DEFERRABLE');

        // Backfill from the legacy single-FK column before dropping it.
        $this->addSql('INSERT INTO core_project_crm_contacts (project_id, contact_id) SELECT id, crm_contact_id FROM core_projects WHERE crm_contact_id IS NOT NULL');

        $this->addSql('ALTER TABLE core_projects DROP CONSTRAINT FK_E351C507A23F13C2');
        $this->addSql('DROP INDEX IDX_E351C507A23F13C2');
        $this->addSql('ALTER TABLE core_projects DROP COLUMN crm_contact_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_projects ADD crm_contact_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_E351C507A23F13C2 ON core_projects (crm_contact_id)');
        $this->addSql('ALTER TABLE core_projects ADD CONSTRAINT FK_E351C507A23F13C2 FOREIGN KEY (crm_contact_id) REFERENCES core_crm_contacts (id) ON DELETE SET NULL NOT DEFERRABLE');

        // Best-effort restore: pick the lowest contact_id per project.
        $this->addSql('UPDATE core_projects p SET crm_contact_id = (SELECT MIN(contact_id) FROM core_project_crm_contacts pcc WHERE pcc.project_id = p.id)');

        $this->addSql('ALTER TABLE core_project_crm_contacts DROP CONSTRAINT FK_PROJECT_CRM_CONTACTS_PROJECT');
        $this->addSql('ALTER TABLE core_project_crm_contacts DROP CONSTRAINT FK_PROJECT_CRM_CONTACTS_CONTACT');
        $this->addSql('DROP TABLE core_project_crm_contacts');
    }
}
