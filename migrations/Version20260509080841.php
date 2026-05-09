<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509080841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE IF EXISTS seq_log CASCADE');
        $this->addSql('CREATE SEQUENCE seq_core_employee_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_employees (first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, job_title VARCHAR(150) DEFAULT NULL, department VARCHAR(100) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, work_email VARCHAR(180) DEFAULT NULL, hired_at DATE DEFAULT NULL, left_at DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5E2F324A76ED395 ON core_employees (user_id)');
        $this->addSql('ALTER TABLE core_employees ADD CONSTRAINT FK_F5E2F324A76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_employee_id CASCADE');
        $this->addSql('CREATE SEQUENCE seq_log INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE core_employees DROP CONSTRAINT FK_F5E2F324A76ED395');
        $this->addSql('DROP TABLE core_employees');
    }
}
