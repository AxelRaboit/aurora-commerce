<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema for the Notes/Block sub-module. Originally created a
 * dedicated `core_blocks` table; that table was later folded into a JSON
 * column on `core_block_notes` (see Version20260517XXXXXX) but the
 * original CREATE is preserved here so existing installs replay the
 * historical sequence correctly.
 */
final class Version20260516222029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_block_notes and core_blocks tables for the Notes/Block sub-module.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_block_note_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_block_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_block_notes (title TEXT DEFAULT NULL, tags JSON DEFAULT \'[]\' NOT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, agency_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_113BFE00CDEADB2A ON core_block_notes (agency_id)');
        $this->addSql('CREATE INDEX idx_block_notes_user ON core_block_notes (user_id)');
        $this->addSql('CREATE INDEX idx_block_notes_parent ON core_block_notes (parent_id)');
        $this->addSql('CREATE TABLE core_blocks (type VARCHAR(32) NOT NULL, data_json TEXT DEFAULT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, note_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_blocks_note ON core_blocks (note_id)');
        $this->addSql('CREATE INDEX idx_blocks_type ON core_blocks (type)');
        $this->addSql('ALTER TABLE core_block_notes ADD CONSTRAINT FK_113BFE00A76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_block_notes ADD CONSTRAINT FK_113BFE00CDEADB2A FOREIGN KEY (agency_id) REFERENCES core_agencies (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_block_notes ADD CONSTRAINT FK_113BFE00727ACA70 FOREIGN KEY (parent_id) REFERENCES core_block_notes (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_blocks ADD CONSTRAINT FK_6EB1F62126ED0855 FOREIGN KEY (note_id) REFERENCES core_block_notes (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_core_block_note_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_block_id CASCADE');
        $this->addSql('ALTER TABLE core_block_notes DROP CONSTRAINT FK_113BFE00A76ED395');
        $this->addSql('ALTER TABLE core_block_notes DROP CONSTRAINT FK_113BFE00CDEADB2A');
        $this->addSql('ALTER TABLE core_block_notes DROP CONSTRAINT FK_113BFE00727ACA70');
        $this->addSql('ALTER TABLE core_blocks DROP CONSTRAINT FK_6EB1F62126ED0855');
        $this->addSql('DROP TABLE core_block_notes');
        $this->addSql('DROP TABLE core_blocks');
    }
}
