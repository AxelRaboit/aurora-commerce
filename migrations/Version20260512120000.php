<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add GED document versioning table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_ged_document_version_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_ged_document_versions (id BIGINT NOT NULL, document_id BIGINT NOT NULL, file_id BIGINT NOT NULL, version_number INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, note VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN core_ged_document_versions.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_ged_version_document ON core_ged_document_versions (document_id)');
        $this->addSql('ALTER TABLE core_ged_document_versions ADD CONSTRAINT fk_ged_version_document FOREIGN KEY (document_id) REFERENCES core_ged_documents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE core_ged_document_versions ADD CONSTRAINT fk_ged_version_file FOREIGN KEY (file_id) REFERENCES core_media (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_ged_document_versions DROP CONSTRAINT fk_ged_version_document');
        $this->addSql('ALTER TABLE core_ged_document_versions DROP CONSTRAINT fk_ged_version_file');
        $this->addSql('DROP TABLE core_ged_document_versions');
        $this->addSql('DROP SEQUENCE seq_core_ged_document_version_id CASCADE');
    }
}
