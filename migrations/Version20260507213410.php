<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507213410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Project: labels, checklist items, time entries, story points/estimate, link to CrmDeal.';
    }

    public function up(Schema $schema): void
    {
        // NOTE: seq_prj, seq_log, seq_tsk, seq_prjc are runtime-managed reference sequences
        // (SequenceGenerator). Doctrine's diff falsely flags them as orphaned — never drop them.
        $this->addSql('CREATE SEQUENCE seq_project_label_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_project_task_item_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_project_task_time_entry_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_project_labels (id INT NOT NULL, name VARCHAR(60) NOT NULL, color VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, project_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_592900D4166D1F9C ON core_project_labels (project_id)');
        $this->addSql('CREATE TABLE core_project_task_items (id INT NOT NULL, label VARCHAR(255) NOT NULL, done BOOLEAN DEFAULT false NOT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, task_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_1174FDD18DB60186 ON core_project_task_items (task_id)');
        $this->addSql('CREATE TABLE core_project_task_time_entries (id INT NOT NULL, minutes INT NOT NULL, note TEXT DEFAULT NULL, logged_at DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, task_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9CB4372D8DB60186 ON core_project_task_time_entries (task_id)');
        $this->addSql('CREATE INDEX IDX_9CB4372DA76ED395 ON core_project_task_time_entries (user_id)');
        $this->addSql('CREATE TABLE core_project_task_labels (task_id INT NOT NULL, label_id INT NOT NULL, PRIMARY KEY (task_id, label_id))');
        $this->addSql('CREATE INDEX IDX_4C98B76A8DB60186 ON core_project_task_labels (task_id)');
        $this->addSql('CREATE INDEX IDX_4C98B76A33B92F39 ON core_project_task_labels (label_id)');
        $this->addSql('ALTER TABLE core_project_labels ADD CONSTRAINT FK_592900D4166D1F9C FOREIGN KEY (project_id) REFERENCES core_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_items ADD CONSTRAINT FK_1174FDD18DB60186 FOREIGN KEY (task_id) REFERENCES core_project_tasks (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_time_entries ADD CONSTRAINT FK_9CB4372D8DB60186 FOREIGN KEY (task_id) REFERENCES core_project_tasks (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_time_entries ADD CONSTRAINT FK_9CB4372DA76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_labels ADD CONSTRAINT FK_4C98B76A8DB60186 FOREIGN KEY (task_id) REFERENCES core_project_tasks (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_labels ADD CONSTRAINT FK_4C98B76A33B92F39 FOREIGN KEY (label_id) REFERENCES core_project_labels (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER INDEX uniq_project_columns_reference RENAME TO UNIQ_472C0E8AAEA34913');
        $this->addSql('ALTER INDEX idx_project_columns_project RENAME TO IDX_472C0E8A166D1F9C');
        $this->addSql('ALTER TABLE core_project_tasks ADD story_points INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_project_tasks ADD estimate_minutes INT DEFAULT NULL');
        $this->addSql('ALTER INDEX idx_project_tasks_column RENAME TO IDX_CBA88656BE8E8ED5');
        $this->addSql('ALTER TABLE core_projects ADD crm_deal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_projects ADD CONSTRAINT FK_E351C5071A456F92 FOREIGN KEY (crm_deal_id) REFERENCES core_crm_deals (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_E351C5071A456F92 ON core_projects (crm_deal_id)');
        $this->addSql('ALTER INDEX idx_project_crm_contacts_project RENAME TO IDX_85089333166D1F9C');
        $this->addSql('ALTER INDEX idx_project_crm_contacts_contact RENAME TO IDX_85089333E7A1254A');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_project_label_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_project_task_item_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_project_task_time_entry_id CASCADE');
        $this->addSql('ALTER TABLE core_project_labels DROP CONSTRAINT FK_592900D4166D1F9C');
        $this->addSql('ALTER TABLE core_project_task_items DROP CONSTRAINT FK_1174FDD18DB60186');
        $this->addSql('ALTER TABLE core_project_task_time_entries DROP CONSTRAINT FK_9CB4372D8DB60186');
        $this->addSql('ALTER TABLE core_project_task_time_entries DROP CONSTRAINT FK_9CB4372DA76ED395');
        $this->addSql('ALTER TABLE core_project_task_labels DROP CONSTRAINT FK_4C98B76A8DB60186');
        $this->addSql('ALTER TABLE core_project_task_labels DROP CONSTRAINT FK_4C98B76A33B92F39');
        $this->addSql('DROP TABLE core_project_labels');
        $this->addSql('DROP TABLE core_project_task_items');
        $this->addSql('DROP TABLE core_project_task_time_entries');
        $this->addSql('DROP TABLE core_project_task_labels');
        $this->addSql('ALTER INDEX uniq_472c0e8aaea34913 RENAME TO uniq_project_columns_reference');
        $this->addSql('ALTER INDEX idx_472c0e8a166d1f9c RENAME TO idx_project_columns_project');
        $this->addSql('ALTER INDEX idx_85089333e7a1254a RENAME TO idx_project_crm_contacts_contact');
        $this->addSql('ALTER INDEX idx_85089333166d1f9c RENAME TO idx_project_crm_contacts_project');
        $this->addSql('ALTER TABLE core_project_tasks DROP story_points');
        $this->addSql('ALTER TABLE core_project_tasks DROP estimate_minutes');
        $this->addSql('ALTER INDEX idx_cba88656be8e8ed5 RENAME TO idx_project_tasks_column');
        $this->addSql('ALTER TABLE core_projects DROP CONSTRAINT FK_E351C5071A456F92');
        $this->addSql('DROP INDEX IDX_E351C5071A456F92');
        $this->addSql('ALTER TABLE core_projects DROP crm_deal_id');
    }
}
