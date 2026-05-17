<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Per-user filesystem mount points the AI assistant is authorised to read
 * (and, in Phase 1B+, write). Each row binds a (user, name, path, access)
 * tuple; the FilesystemReadTool enforces that any requested path lies
 * under one of the user's active entries.
 */
final class Version20260517160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_assistant_mount_points table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_assistant_mount_point_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_assistant_mount_points (id INT NOT NULL, user_id INT NOT NULL, name VARCHAR(100) NOT NULL, path VARCHAR(1024) NOT NULL, access VARCHAR(20) NOT NULL, active BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_assistant_mount_points_user ON core_assistant_mount_points (user_id)');
        $this->addSql('ALTER TABLE core_assistant_mount_points ADD CONSTRAINT FK_ASSISTMP_USER FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_assistant_mount_points DROP CONSTRAINT FK_ASSISTMP_USER');
        $this->addSql('DROP TABLE core_assistant_mount_points');
        $this->addSql('DROP SEQUENCE seq_core_assistant_mount_point_id CASCADE');
    }
}
