<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260530143013 extends AbstractMigration
{
    public function getDescription(): string
    {
        // Monorepo-split cat. D: Project→CRM links become soft references so
        // Project depends on no other module. crmCompany/crmDeal drop their FKs
        // (columns crm_company_id/crm_deal_id kept as plain ints); the
        // many-to-many crmContacts join table collapses into a crm_contact_ids
        // JSON column on core_projects, preserving the existing links.
        return 'Soft-reference Project→CRM (drop FKs, collapse contacts m2m into JSON)';
    }

    public function up(Schema $schema): void
    {
        // 1. Add the JSON column first so we can migrate the join-table data into it.
        $this->addSql('ALTER TABLE core_projects ADD crm_contact_ids JSON DEFAULT \'[]\' NOT NULL');

        // 2. Data migration: collapse the project↔contact join table into an
        //    ordered JSON array of contact ids on each project.
        $this->addSql(<<<'SQL'
            UPDATE core_projects p SET crm_contact_ids = COALESCE(
                (SELECT json_agg(pc.contact_id ORDER BY pc.contact_id)
                 FROM core_project_crm_contacts pc
                 WHERE pc.project_id = p.id),
                '[]'
            )
            SQL);

        // 3. Drop the join table.
        $this->addSql('ALTER TABLE core_project_crm_contacts DROP CONSTRAINT fk_85089333e7a1254a');
        $this->addSql('ALTER TABLE core_project_crm_contacts DROP CONSTRAINT fk_85089333166d1f9c');
        $this->addSql('DROP TABLE core_project_crm_contacts');

        // 4. Drop the company/deal cross-module FKs (columns kept as plain ints).
        $this->addSql('ALTER TABLE core_projects DROP CONSTRAINT fk_e351c5071a456f92');
        $this->addSql('ALTER TABLE core_projects DROP CONSTRAINT fk_e351c507d2052c5e');
        $this->addSql('DROP INDEX idx_e351c507d2052c5e');
        $this->addSql('DROP INDEX idx_e351c5071a456f92');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE core_project_crm_contacts (project_id INT NOT NULL, contact_id INT NOT NULL, PRIMARY KEY (project_id, contact_id))');
        $this->addSql('CREATE INDEX idx_85089333166d1f9c ON core_project_crm_contacts (project_id)');
        $this->addSql('CREATE INDEX idx_85089333e7a1254a ON core_project_crm_contacts (contact_id)');
        $this->addSql('ALTER TABLE core_project_crm_contacts ADD CONSTRAINT fk_85089333e7a1254a FOREIGN KEY (contact_id) REFERENCES core_crm_contacts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE core_project_crm_contacts ADD CONSTRAINT fk_85089333166d1f9c FOREIGN KEY (project_id) REFERENCES core_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE core_projects DROP crm_contact_ids');
        $this->addSql('ALTER TABLE core_projects ADD CONSTRAINT fk_e351c5071a456f92 FOREIGN KEY (crm_deal_id) REFERENCES core_crm_deals (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE core_projects ADD CONSTRAINT fk_e351c507d2052c5e FOREIGN KEY (crm_company_id) REFERENCES core_crm_companies (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_e351c507d2052c5e ON core_projects (crm_company_id)');
        $this->addSql('CREATE INDEX idx_e351c5071a456f92 ON core_projects (crm_deal_id)');
    }
}
