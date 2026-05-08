<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508195648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_log CASCADE');
        $this->addSql('DROP SEQUENCE seq_prj CASCADE');
        $this->addSql('DROP SEQUENCE seq_tsk CASCADE');
        $this->addSql('DROP SEQUENCE seq_prjc CASCADE');
        $this->addSql('CREATE SEQUENCE seq_core_core_planning_event_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_core_planning_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_planning_events (title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, start_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, all_day BOOLEAN DEFAULT false NOT NULL, status VARCHAR(20) DEFAULT \'confirmed\' NOT NULL, source_type VARCHAR(64) DEFAULT NULL, source_id INT DEFAULT NULL, source_label VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, planning_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_4E5AB77D3D865311 ON core_planning_events (planning_id)');
        $this->addSql('CREATE INDEX idx_planning_event_planning_start ON core_planning_events (planning_id, start_at)');
        $this->addSql('CREATE UNIQUE INDEX uniq_planning_event_source ON core_planning_events (source_type, source_id)');
        $this->addSql('CREATE TABLE core_planning_event_attendees (event_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (event_id, user_id))');
        $this->addSql('CREATE INDEX IDX_F280AEA271F7E88B ON core_planning_event_attendees (event_id)');
        $this->addSql('CREATE INDEX IDX_F280AEA2A76ED395 ON core_planning_event_attendees (user_id)');
        $this->addSql('CREATE TABLE core_plannings (name VARCHAR(150) NOT NULL, description TEXT DEFAULT NULL, color VARCHAR(7) DEFAULT \'#3b82f6\' NOT NULL, timezone VARCHAR(64) DEFAULT \'Europe/Paris\' NOT NULL, visibility VARCHAR(20) DEFAULT \'private\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, owner_id INT DEFAULT NULL, agency_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6431B97E3C61F9 ON core_plannings (owner_id)');
        $this->addSql('CREATE INDEX IDX_6431B9CDEADB2A ON core_plannings (agency_id)');
        $this->addSql('ALTER TABLE core_planning_events ADD CONSTRAINT FK_4E5AB77D3D865311 FOREIGN KEY (planning_id) REFERENCES core_plannings (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_planning_event_attendees ADD CONSTRAINT FK_F280AEA271F7E88B FOREIGN KEY (event_id) REFERENCES core_planning_events (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_planning_event_attendees ADD CONSTRAINT FK_F280AEA2A76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_plannings ADD CONSTRAINT FK_6431B97E3C61F9 FOREIGN KEY (owner_id) REFERENCES core_users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_plannings ADD CONSTRAINT FK_6431B9CDEADB2A FOREIGN KEY (agency_id) REFERENCES core_agencies (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_core_planning_event_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_core_planning_id CASCADE');
        $this->addSql('CREATE SEQUENCE seq_log INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_prj INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_tsk INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_prjc INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE core_planning_events DROP CONSTRAINT FK_4E5AB77D3D865311');
        $this->addSql('ALTER TABLE core_planning_event_attendees DROP CONSTRAINT FK_F280AEA271F7E88B');
        $this->addSql('ALTER TABLE core_planning_event_attendees DROP CONSTRAINT FK_F280AEA2A76ED395');
        $this->addSql('ALTER TABLE core_plannings DROP CONSTRAINT FK_6431B97E3C61F9');
        $this->addSql('ALTER TABLE core_plannings DROP CONSTRAINT FK_6431B9CDEADB2A');
        $this->addSql('DROP TABLE core_planning_events');
        $this->addSql('DROP TABLE core_planning_event_attendees');
        $this->addSql('DROP TABLE core_plannings');
    }
}
