<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replace ProjectTask.status enum with a per-project ProjectColumn FK so users
 * can define their own Kanban columns (Trello-style).
 */
final class Version20260507200933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Project: replace task status enum with per-project columns (custom Kanban columns).';
    }

    public function up(Schema $schema): void
    {
        // Sequence + table for ProjectColumn entity.
        $this->addSql('CREATE SEQUENCE seq_project_column_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_project_columns (
            id INT NOT NULL,
            reference VARCHAR(32) DEFAULT NULL,
            project_id INT NOT NULL,
            label VARCHAR(100) NOT NULL,
            position INT DEFAULT 0 NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROJECT_COLUMNS_REFERENCE ON core_project_columns (reference)');
        $this->addSql('CREATE INDEX IDX_PROJECT_COLUMNS_PROJECT ON core_project_columns (project_id)');
        $this->addSql('ALTER TABLE core_project_columns ADD CONSTRAINT FK_PROJECT_COLUMNS_PROJECT FOREIGN KEY (project_id) REFERENCES core_projects (id) ON DELETE CASCADE NOT DEFERRABLE');

        // Backfill: for each existing project, create 3 default columns (À faire / En cours / Terminé).
        $this->addSql('INSERT INTO core_project_columns (id, project_id, label, position, created_at, updated_at) '
            ."SELECT nextval('seq_project_column_id'), p.id, 'À faire', 0, NOW(), NOW() FROM core_projects p");
        $this->addSql('INSERT INTO core_project_columns (id, project_id, label, position, created_at, updated_at) '
            ."SELECT nextval('seq_project_column_id'), p.id, 'En cours', 1, NOW(), NOW() FROM core_projects p");
        $this->addSql('INSERT INTO core_project_columns (id, project_id, label, position, created_at, updated_at) '
            ."SELECT nextval('seq_project_column_id'), p.id, 'Terminé', 2, NOW(), NOW() FROM core_projects p");

        // Add column_id to tasks (nullable for backfill, will become NOT NULL after).
        $this->addSql('ALTER TABLE core_project_tasks ADD column_id INT DEFAULT NULL');

        // Map each task's enum status to its project's matching column.
        // todo → position 0, in_progress → position 1, done → position 2, cancelled → position 2 (Terminé).
        $this->addSql("UPDATE core_project_tasks t SET column_id = (
            SELECT c.id FROM core_project_columns c
            WHERE c.project_id = t.project_id
              AND c.position = CASE t.status
                  WHEN 'todo' THEN 0
                  WHEN 'in_progress' THEN 1
                  WHEN 'done' THEN 2
                  WHEN 'cancelled' THEN 2
                  ELSE 0
              END
        )");

        // Lock the FK + drop the legacy enum column.
        $this->addSql('ALTER TABLE core_project_tasks ALTER COLUMN column_id SET NOT NULL');
        $this->addSql('CREATE INDEX IDX_PROJECT_TASKS_COLUMN ON core_project_tasks (column_id)');
        $this->addSql('ALTER TABLE core_project_tasks ADD CONSTRAINT FK_PROJECT_TASKS_COLUMN FOREIGN KEY (column_id) REFERENCES core_project_columns (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_tasks DROP COLUMN status');
    }

    public function down(Schema $schema): void
    {
        // Restore status column.
        $this->addSql("ALTER TABLE core_project_tasks ADD status VARCHAR(20) DEFAULT 'todo' NOT NULL");

        // Best-effort restore: map column position back to enum value.
        $this->addSql("UPDATE core_project_tasks t SET status = CASE (
            SELECT c.position FROM core_project_columns c WHERE c.id = t.column_id
        )
            WHEN 0 THEN 'todo'
            WHEN 1 THEN 'in_progress'
            WHEN 2 THEN 'done'
            ELSE 'todo'
        END");

        $this->addSql('ALTER TABLE core_project_tasks DROP CONSTRAINT FK_PROJECT_TASKS_COLUMN');
        $this->addSql('DROP INDEX IDX_PROJECT_TASKS_COLUMN');
        $this->addSql('ALTER TABLE core_project_tasks DROP COLUMN column_id');

        $this->addSql('ALTER TABLE core_project_columns DROP CONSTRAINT FK_PROJECT_COLUMNS_PROJECT');
        $this->addSql('DROP TABLE core_project_columns');
        $this->addSql('DROP SEQUENCE seq_project_column_id CASCADE');
    }
}
