<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523181528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Welding module — instance layer: Workflow + WorkflowStep, with FK to WorkflowTemplate / WorkflowStepTemplate (RESTRICT), Employee assignee (SET NULL), and core_users for completedBy/validatedBy (SET NULL).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_welding_workflow_step_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_welding_workflow_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_welding_workflow_steps (position INT DEFAULT 0 NOT NULL, status VARCHAR(30) DEFAULT \'pending\' NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, validated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, validation_comment TEXT DEFAULT NULL, rejection_comment TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, workflow_id INT NOT NULL, step_template_id INT NOT NULL, completed_by_id INT DEFAULT NULL, validated_by_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_89F6344A2C7C2CBA ON core_welding_workflow_steps (workflow_id)');
        $this->addSql('CREATE INDEX IDX_89F6344A40D1598 ON core_welding_workflow_steps (step_template_id)');
        $this->addSql('CREATE INDEX IDX_89F6344A85ECDE76 ON core_welding_workflow_steps (completed_by_id)');
        $this->addSql('CREATE INDEX IDX_89F6344AC69DE5E5 ON core_welding_workflow_steps (validated_by_id)');
        $this->addSql('CREATE TABLE core_welding_workflows (reference VARCHAR(64) DEFAULT NULL, status VARCHAR(30) DEFAULT \'draft\' NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, rejected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, rejection_reason TEXT DEFAULT NULL, context_type VARCHAR(100) DEFAULT NULL, context_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, template_id INT NOT NULL, assignee_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B206CAFAEA34913 ON core_welding_workflows (reference)');
        $this->addSql('CREATE INDEX IDX_2B206CAF5DA0FB8 ON core_welding_workflows (template_id)');
        $this->addSql('CREATE INDEX IDX_2B206CAF59EC7D60 ON core_welding_workflows (assignee_id)');
        $this->addSql('ALTER TABLE core_welding_workflow_steps ADD CONSTRAINT FK_89F6344A2C7C2CBA FOREIGN KEY (workflow_id) REFERENCES core_welding_workflows (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_welding_workflow_steps ADD CONSTRAINT FK_89F6344A40D1598 FOREIGN KEY (step_template_id) REFERENCES core_welding_workflow_step_templates (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_welding_workflow_steps ADD CONSTRAINT FK_89F6344A85ECDE76 FOREIGN KEY (completed_by_id) REFERENCES core_users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_welding_workflow_steps ADD CONSTRAINT FK_89F6344AC69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES core_users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_welding_workflows ADD CONSTRAINT FK_2B206CAF5DA0FB8 FOREIGN KEY (template_id) REFERENCES core_welding_workflow_templates (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_welding_workflows ADD CONSTRAINT FK_2B206CAF59EC7D60 FOREIGN KEY (assignee_id) REFERENCES core_employees (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_welding_workflow_steps DROP CONSTRAINT FK_89F6344A2C7C2CBA');
        $this->addSql('ALTER TABLE core_welding_workflow_steps DROP CONSTRAINT FK_89F6344A40D1598');
        $this->addSql('ALTER TABLE core_welding_workflow_steps DROP CONSTRAINT FK_89F6344A85ECDE76');
        $this->addSql('ALTER TABLE core_welding_workflow_steps DROP CONSTRAINT FK_89F6344AC69DE5E5');
        $this->addSql('ALTER TABLE core_welding_workflows DROP CONSTRAINT FK_2B206CAF5DA0FB8');
        $this->addSql('ALTER TABLE core_welding_workflows DROP CONSTRAINT FK_2B206CAF59EC7D60');
        $this->addSql('DROP TABLE core_welding_workflow_steps');
        $this->addSql('DROP TABLE core_welding_workflows');
        $this->addSql('DROP SEQUENCE seq_core_welding_workflow_step_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_welding_workflow_id CASCADE');
    }
}
