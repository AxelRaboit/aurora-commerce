<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507221417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Project: comments, attachments, watchers, sprints, saved views + Notifications.';
    }

    public function up(Schema $schema): void
    {
        // NOTE: seq_prj, seq_log, seq_tsk, seq_prjc are runtime-managed reference sequences
        // (SequenceGenerator). Doctrine's diff falsely flags them as orphaned — never drop them.
        $this->addSql('CREATE SEQUENCE seq_notification_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_project_saved_view_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_project_sprint_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_project_task_comment_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_notifications (id INT NOT NULL, type VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, body TEXT DEFAULT NULL, url VARCHAR(500) DEFAULT NULL, data JSON DEFAULT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, recipient_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E8A55A8CE92F8F78 ON core_notifications (recipient_id)');
        $this->addSql('CREATE TABLE core_project_saved_views (id INT NOT NULL, name VARCHAR(100) NOT NULL, filters JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, owner_id INT NOT NULL, project_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_BEC376367E3C61F9 ON core_project_saved_views (owner_id)');
        $this->addSql('CREATE INDEX IDX_BEC37636166D1F9C ON core_project_saved_views (project_id)');
        $this->addSql('CREATE TABLE core_project_sprints (id INT NOT NULL, name VARCHAR(100) NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, is_active BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, project_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_A506A74C166D1F9C ON core_project_sprints (project_id)');
        $this->addSql('CREATE TABLE core_project_task_comments (id INT NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, task_id INT NOT NULL, author_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_285E885D8DB60186 ON core_project_task_comments (task_id)');
        $this->addSql('CREATE INDEX IDX_285E885DF675F31B ON core_project_task_comments (author_id)');
        $this->addSql('CREATE TABLE core_project_task_attachments (task_id INT NOT NULL, media_id INT NOT NULL, PRIMARY KEY (task_id, media_id))');
        $this->addSql('CREATE INDEX IDX_595F791B8DB60186 ON core_project_task_attachments (task_id)');
        $this->addSql('CREATE INDEX IDX_595F791BEA9FDD75 ON core_project_task_attachments (media_id)');
        $this->addSql('CREATE TABLE core_project_task_watchers (task_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (task_id, user_id))');
        $this->addSql('CREATE INDEX IDX_B31CB1598DB60186 ON core_project_task_watchers (task_id)');
        $this->addSql('CREATE INDEX IDX_B31CB159A76ED395 ON core_project_task_watchers (user_id)');
        $this->addSql('ALTER TABLE core_notifications ADD CONSTRAINT FK_E8A55A8CE92F8F78 FOREIGN KEY (recipient_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_saved_views ADD CONSTRAINT FK_BEC376367E3C61F9 FOREIGN KEY (owner_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_saved_views ADD CONSTRAINT FK_BEC37636166D1F9C FOREIGN KEY (project_id) REFERENCES core_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_sprints ADD CONSTRAINT FK_A506A74C166D1F9C FOREIGN KEY (project_id) REFERENCES core_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_comments ADD CONSTRAINT FK_285E885D8DB60186 FOREIGN KEY (task_id) REFERENCES core_project_tasks (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_comments ADD CONSTRAINT FK_285E885DF675F31B FOREIGN KEY (author_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_attachments ADD CONSTRAINT FK_595F791B8DB60186 FOREIGN KEY (task_id) REFERENCES core_project_tasks (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_attachments ADD CONSTRAINT FK_595F791BEA9FDD75 FOREIGN KEY (media_id) REFERENCES core_media (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_watchers ADD CONSTRAINT FK_B31CB1598DB60186 FOREIGN KEY (task_id) REFERENCES core_project_tasks (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_task_watchers ADD CONSTRAINT FK_B31CB159A76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_tasks ADD sprint_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_project_tasks ADD CONSTRAINT FK_CBA886568C24077B FOREIGN KEY (sprint_id) REFERENCES core_project_sprints (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_CBA886568C24077B ON core_project_tasks (sprint_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_notification_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_project_saved_view_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_project_sprint_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_project_task_comment_id CASCADE');
        $this->addSql('ALTER TABLE core_notifications DROP CONSTRAINT FK_E8A55A8CE92F8F78');
        $this->addSql('ALTER TABLE core_project_saved_views DROP CONSTRAINT FK_BEC376367E3C61F9');
        $this->addSql('ALTER TABLE core_project_saved_views DROP CONSTRAINT FK_BEC37636166D1F9C');
        $this->addSql('ALTER TABLE core_project_sprints DROP CONSTRAINT FK_A506A74C166D1F9C');
        $this->addSql('ALTER TABLE core_project_task_comments DROP CONSTRAINT FK_285E885D8DB60186');
        $this->addSql('ALTER TABLE core_project_task_comments DROP CONSTRAINT FK_285E885DF675F31B');
        $this->addSql('ALTER TABLE core_project_task_attachments DROP CONSTRAINT FK_595F791B8DB60186');
        $this->addSql('ALTER TABLE core_project_task_attachments DROP CONSTRAINT FK_595F791BEA9FDD75');
        $this->addSql('ALTER TABLE core_project_task_watchers DROP CONSTRAINT FK_B31CB1598DB60186');
        $this->addSql('ALTER TABLE core_project_task_watchers DROP CONSTRAINT FK_B31CB159A76ED395');
        $this->addSql('DROP TABLE core_notifications');
        $this->addSql('DROP TABLE core_project_saved_views');
        $this->addSql('DROP TABLE core_project_sprints');
        $this->addSql('DROP TABLE core_project_task_comments');
        $this->addSql('DROP TABLE core_project_task_attachments');
        $this->addSql('DROP TABLE core_project_task_watchers');
        $this->addSql('ALTER TABLE core_project_tasks DROP CONSTRAINT FK_CBA886568C24077B');
        $this->addSql('DROP INDEX IDX_CBA886568C24077B');
        $this->addSql('ALTER TABLE core_project_tasks DROP sprint_id');
    }
}
