<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds core_markdown_notes table (Notes module — Markdown sub-module).
 * Hierarchical (self-ref parent_id), per-user with snapshot of agency_id
 * for future agency-wide queries. title/content stored encrypted (libsodium).
 */
final class Version20260515180019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_markdown_notes table for the Notes/Markdown sub-module.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_markdown_note_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_markdown_notes (title TEXT DEFAULT NULL, content TEXT DEFAULT NULL, tags JSON DEFAULT \'[]\' NOT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, agency_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_650ACC0BCDEADB2A ON core_markdown_notes (agency_id)');
        $this->addSql('CREATE INDEX idx_markdown_notes_user ON core_markdown_notes (user_id)');
        $this->addSql('CREATE INDEX idx_markdown_notes_parent ON core_markdown_notes (parent_id)');
        $this->addSql('ALTER TABLE core_markdown_notes ADD CONSTRAINT FK_650ACC0BA76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_markdown_notes ADD CONSTRAINT FK_650ACC0BCDEADB2A FOREIGN KEY (agency_id) REFERENCES core_agencies (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_markdown_notes ADD CONSTRAINT FK_650ACC0B727ACA70 FOREIGN KEY (parent_id) REFERENCES core_markdown_notes (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_core_markdown_note_id CASCADE');
        $this->addSql('ALTER TABLE core_markdown_notes DROP CONSTRAINT FK_650ACC0BA76ED395');
        $this->addSql('ALTER TABLE core_markdown_notes DROP CONSTRAINT FK_650ACC0BCDEADB2A');
        $this->addSql('ALTER TABLE core_markdown_notes DROP CONSTRAINT FK_650ACC0B727ACA70');
        $this->addSql('DROP TABLE core_markdown_notes');
    }
}
