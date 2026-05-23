<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Welding workflow tasks — template + instance tables.
 *
 * Tasks are user-facing checklist items attached to a workflow step. The
 * template table stores the definitions filled in by admins when designing
 * a workflow template; the instance table holds the snapshot taken at
 * workflow start time + the runtime `done/doneBy/doneAt` state filled in by
 * the welder while running the workflow.
 */
final class Version20260524200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add welding workflow step task tables (template + instance).';
    }

    public function up(Schema $schema): void
    {
        // === Template tasks (admin-defined checklist items per step template) ===
        $this->addSql('CREATE SEQUENCE seq_core_welding_workflow_step_task_template_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_welding_workflow_step_task_templates (
            id INT NOT NULL,
            workflow_step_template_id INT NOT NULL,
            position INT DEFAULT 0 NOT NULL,
            label VARCHAR(300) NOT NULL,
            description TEXT DEFAULT NULL,
            required BOOLEAN DEFAULT true NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE INDEX IDX_2E60CD986BE5D675 ON core_welding_workflow_step_task_templates (workflow_step_template_id)');
        $this->addSql('ALTER TABLE core_welding_workflow_step_task_templates ADD CONSTRAINT fk_welding_task_tpl_step FOREIGN KEY (workflow_step_template_id) REFERENCES core_welding_workflow_step_templates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // === Instance tasks (runtime checklist items snapshotted at workflow start) ===
        $this->addSql('CREATE SEQUENCE seq_core_welding_workflow_step_task_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_welding_workflow_step_tasks (
            id INT NOT NULL,
            workflow_step_id INT NOT NULL,
            task_template_id INT DEFAULT NULL,
            done_by_id INT DEFAULT NULL,
            position INT DEFAULT 0 NOT NULL,
            label VARCHAR(300) NOT NULL,
            description TEXT DEFAULT NULL,
            required BOOLEAN DEFAULT true NOT NULL,
            done BOOLEAN DEFAULT false NOT NULL,
            done_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE INDEX IDX_1634A3A471FE882C ON core_welding_workflow_step_tasks (workflow_step_id)');
        $this->addSql('CREATE INDEX IDX_1634A3A443AFA28A ON core_welding_workflow_step_tasks (task_template_id)');
        $this->addSql('CREATE INDEX IDX_1634A3A435AE3EF9 ON core_welding_workflow_step_tasks (done_by_id)');
        $this->addSql('ALTER TABLE core_welding_workflow_step_tasks ADD CONSTRAINT fk_welding_task_step FOREIGN KEY (workflow_step_id) REFERENCES core_welding_workflow_steps (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE core_welding_workflow_step_tasks ADD CONSTRAINT fk_welding_task_tpl FOREIGN KEY (task_template_id) REFERENCES core_welding_workflow_step_task_templates (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE core_welding_workflow_step_tasks ADD CONSTRAINT fk_welding_task_done_by FOREIGN KEY (done_by_id) REFERENCES core_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_welding_workflow_step_tasks DROP CONSTRAINT fk_welding_task_step');
        $this->addSql('ALTER TABLE core_welding_workflow_step_tasks DROP CONSTRAINT fk_welding_task_tpl');
        $this->addSql('ALTER TABLE core_welding_workflow_step_tasks DROP CONSTRAINT fk_welding_task_done_by');
        $this->addSql('DROP TABLE core_welding_workflow_step_tasks');
        $this->addSql('DROP SEQUENCE seq_core_welding_workflow_step_task_id CASCADE');

        $this->addSql('ALTER TABLE core_welding_workflow_step_task_templates DROP CONSTRAINT fk_welding_task_tpl_step');
        $this->addSql('DROP TABLE core_welding_workflow_step_task_templates');
        $this->addSql('DROP SEQUENCE seq_core_welding_workflow_step_task_template_id CASCADE');
    }
}
