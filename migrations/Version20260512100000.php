<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add DocumentTag and DocumentFolder sub-modules to GED.
 *
 * Creates:
 *   - core_ged_document_tags (id, name, color)
 *   - core_ged_document_folders (id, parent_id, name, position)
 *   - core_ged_document_tag_map (document_id, document_tag_id) — ManyToMany join table
 *   - Adds folder_id column to core_ged_documents
 *   - Sequences for the two new entities
 */
final class Version20260512100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add GED DocumentTag and DocumentFolder sub-modules with sequences, tables, join table and folder FK on documents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_ged_document_tag_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_ged_document_folder_id INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE core_ged_document_tags (id BIGINT NOT NULL, name VARCHAR(100) NOT NULL, color VARCHAR(7) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_core_ged_document_tag_name ON core_ged_document_tags (name)');

        $this->addSql('CREATE TABLE core_ged_document_folders (id BIGINT NOT NULL, parent_id BIGINT DEFAULT NULL, name VARCHAR(150) NOT NULL, position INT DEFAULT 0 NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_core_ged_document_folder_position ON core_ged_document_folders (position, name)');
        $this->addSql('ALTER TABLE core_ged_document_folders ADD CONSTRAINT fk_ged_folder_parent FOREIGN KEY (parent_id) REFERENCES core_ged_document_folders (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE core_ged_document_tag_map (document_id BIGINT NOT NULL, document_tag_id BIGINT NOT NULL, PRIMARY KEY (document_id, document_tag_id))');
        $this->addSql('CREATE INDEX idx_ged_tag_map_document ON core_ged_document_tag_map (document_id)');
        $this->addSql('CREATE INDEX idx_ged_tag_map_tag ON core_ged_document_tag_map (document_tag_id)');
        $this->addSql('ALTER TABLE core_ged_document_tag_map ADD CONSTRAINT fk_ged_tag_map_document FOREIGN KEY (document_id) REFERENCES core_ged_documents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE core_ged_document_tag_map ADD CONSTRAINT fk_ged_tag_map_tag FOREIGN KEY (document_tag_id) REFERENCES core_ged_document_tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE core_ged_documents ADD folder_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_ged_documents ADD CONSTRAINT fk_ged_document_folder FOREIGN KEY (folder_id) REFERENCES core_ged_document_folders (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_core_ged_document_folder ON core_ged_documents (folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_ged_documents DROP CONSTRAINT fk_ged_document_folder');
        $this->addSql('DROP INDEX idx_core_ged_document_folder');
        $this->addSql('ALTER TABLE core_ged_documents DROP COLUMN folder_id');

        $this->addSql('ALTER TABLE core_ged_document_tag_map DROP CONSTRAINT fk_ged_tag_map_document');
        $this->addSql('ALTER TABLE core_ged_document_tag_map DROP CONSTRAINT fk_ged_tag_map_tag');
        $this->addSql('DROP TABLE core_ged_document_tag_map');

        $this->addSql('ALTER TABLE core_ged_document_folders DROP CONSTRAINT fk_ged_folder_parent');
        $this->addSql('DROP TABLE core_ged_document_folders');

        $this->addSql('DROP TABLE core_ged_document_tags');

        $this->addSql('DROP SEQUENCE seq_core_ged_document_folder_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_ged_document_tag_id CASCADE');
    }
}
