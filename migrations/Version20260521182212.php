<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521182212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add core_post_it_notes table for the Notes/PostIt sub-module.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_post_it_note_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<'SQL'
                CREATE TABLE core_post_it_notes (
                  title TEXT DEFAULT NULL,
                  content TEXT DEFAULT NULL,
                  color VARCHAR(7) DEFAULT '#FFEB3B' NOT NULL,
                  position_x INT DEFAULT 0 NOT NULL,
                  position_y INT DEFAULT 0 NOT NULL,
                  created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                  updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                  id INT NOT NULL,
                  user_id INT NOT NULL,
                  agency_id INT DEFAULT NULL,
                  PRIMARY KEY (id)
                )
            SQL);
        $this->addSql('CREATE INDEX IDX_BF08188CCDEADB2A ON core_post_it_notes (agency_id)');
        $this->addSql('CREATE INDEX idx_post_it_notes_user ON core_post_it_notes (user_id)');
        $this->addSql(<<<'SQL'
                ALTER TABLE
                  core_post_it_notes
                ADD
                  CONSTRAINT FK_BF08188CA76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE
            SQL);
        $this->addSql(<<<'SQL'
                ALTER TABLE
                  core_post_it_notes
                ADD
                  CONSTRAINT FK_BF08188CCDEADB2A FOREIGN KEY (agency_id) REFERENCES core_agencies (id) ON DELETE
                SET
                  NULL NOT DEFERRABLE
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_post_it_notes DROP CONSTRAINT FK_BF08188CA76ED395');
        $this->addSql('ALTER TABLE core_post_it_notes DROP CONSTRAINT FK_BF08188CCDEADB2A');
        $this->addSql('DROP TABLE core_post_it_notes');
        $this->addSql('DROP SEQUENCE seq_core_post_it_note_id CASCADE');
    }
}
