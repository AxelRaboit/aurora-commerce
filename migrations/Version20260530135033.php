<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260530135033 extends AbstractMigration
{
    public function getDescription(): string
    {
        // Monorepo-split cat. D: Billing.Tiers→Crm.Company becomes a soft
        // reference (plain company_id int, no FK) so Billing depends on no
        // other module and works without Crm installed. Drops the cross-module
        // FK + its index; the column and its data are kept.
        // NB: down() re-adds the FK referencing core_crm_companies — only valid
        // while Crm is installed (migration partitioning is a Phase-5 concern).
        return 'Soft-reference Billing Tiers→CRM Company (drop cross-module FK)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_billing_tiers DROP CONSTRAINT fk_9b9df4d0979b1ad6');
        $this->addSql('DROP INDEX idx_9b9df4d0979b1ad6');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_billing_tiers ADD CONSTRAINT fk_9b9df4d0979b1ad6 FOREIGN KEY (company_id) REFERENCES core_crm_companies (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_9b9df4d0979b1ad6 ON core_billing_tiers (company_id)');
    }
}
