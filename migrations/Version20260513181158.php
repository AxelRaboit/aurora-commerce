<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513181158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_form_fields ADD conditions JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE core_form_fields ADD conditions_logic VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE core_form_fields ADD step INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_forms ADD webhook_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE core_forms ADD crm_sync BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE core_forms ADD steps JSON DEFAULT NULL');
        $this->addSql('DROP INDEX idx_core_ged_document_folder_position');
        $this->addSql('ALTER TABLE core_ged_document_folders ALTER id TYPE INT');
        $this->addSql('ALTER TABLE core_ged_document_folders ALTER parent_id TYPE INT');
        $this->addSql('DROP INDEX idx_core_ged_document_tag_name');
        $this->addSql('ALTER TABLE core_ged_document_tags ALTER id TYPE INT');
        $this->addSql('ALTER TABLE core_ged_document_versions ALTER id TYPE INT');
        $this->addSql('ALTER TABLE core_ged_document_versions ALTER document_id TYPE INT');
        $this->addSql('ALTER TABLE core_ged_document_versions ALTER file_id TYPE INT');
        $this->addSql('COMMENT ON COLUMN core_ged_document_versions.created_at IS \'\'');
        $this->addSql('ALTER INDEX idx_ged_version_document RENAME TO IDX_1392373DC33F7837');
        $this->addSql('ALTER TABLE core_ged_documents ALTER folder_id TYPE INT');
        $this->addSql('ALTER INDEX idx_core_ged_document_folder RENAME TO IDX_A80B359A162CB942');
        $this->addSql('ALTER TABLE core_ged_document_tag_map ALTER document_id TYPE INT');
        $this->addSql('ALTER TABLE core_ged_document_tag_map ALTER document_tag_id TYPE INT');
        $this->addSql('ALTER INDEX idx_ged_tag_map_document RENAME TO IDX_DCAAF837C33F7837');
        $this->addSql('ALTER INDEX idx_ged_tag_map_tag RENAME TO IDX_DCAAF8374B0D277');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_form_fields DROP conditions');
        $this->addSql('ALTER TABLE core_form_fields DROP conditions_logic');
        $this->addSql('ALTER TABLE core_form_fields DROP step');
        $this->addSql('ALTER TABLE core_forms DROP webhook_url');
        $this->addSql('ALTER TABLE core_forms DROP crm_sync');
        $this->addSql('ALTER TABLE core_forms DROP steps');
        $this->addSql('ALTER TABLE core_ged_document_folders ALTER id TYPE BIGINT');
        $this->addSql('ALTER TABLE core_ged_document_folders ALTER parent_id TYPE BIGINT');
        $this->addSql('CREATE INDEX idx_core_ged_document_folder_position ON core_ged_document_folders ("position", name)');
        $this->addSql('ALTER TABLE core_ged_document_tag_map ALTER document_id TYPE BIGINT');
        $this->addSql('ALTER TABLE core_ged_document_tag_map ALTER document_tag_id TYPE BIGINT');
        $this->addSql('ALTER INDEX idx_dcaaf837c33f7837 RENAME TO idx_ged_tag_map_document');
        $this->addSql('ALTER INDEX idx_dcaaf8374b0d277 RENAME TO idx_ged_tag_map_tag');
        $this->addSql('ALTER TABLE core_ged_document_tags ALTER id TYPE BIGINT');
        $this->addSql('CREATE INDEX idx_core_ged_document_tag_name ON core_ged_document_tags (name)');
        $this->addSql('ALTER TABLE core_ged_document_versions ALTER id TYPE BIGINT');
        $this->addSql('ALTER TABLE core_ged_document_versions ALTER document_id TYPE BIGINT');
        $this->addSql('ALTER TABLE core_ged_document_versions ALTER file_id TYPE BIGINT');
        $this->addSql('COMMENT ON COLUMN core_ged_document_versions.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER INDEX idx_1392373dc33f7837 RENAME TO idx_ged_version_document');
        $this->addSql('ALTER TABLE core_ged_documents ALTER folder_id TYPE BIGINT');
        $this->addSql('ALTER INDEX idx_a80b359a162cb942 RENAME TO idx_core_ged_document_folder');
    }
}
