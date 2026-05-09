<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509083101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_employees ADD service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_employees ADD agency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_employees DROP department');
        $this->addSql('ALTER TABLE core_employees ADD CONSTRAINT FK_F5E2F324ED5CA9E6 FOREIGN KEY (service_id) REFERENCES core_services (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_employees ADD CONSTRAINT FK_F5E2F324CDEADB2A FOREIGN KEY (agency_id) REFERENCES core_agencies (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_F5E2F324ED5CA9E6 ON core_employees (service_id)');
        $this->addSql('CREATE INDEX IDX_F5E2F324CDEADB2A ON core_employees (agency_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_employees DROP CONSTRAINT FK_F5E2F324ED5CA9E6');
        $this->addSql('ALTER TABLE core_employees DROP CONSTRAINT FK_F5E2F324CDEADB2A');
        $this->addSql('DROP INDEX IDX_F5E2F324ED5CA9E6');
        $this->addSql('DROP INDEX IDX_F5E2F324CDEADB2A');
        $this->addSql('ALTER TABLE core_employees ADD department VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE core_employees DROP service_id');
        $this->addSql('ALTER TABLE core_employees DROP agency_id');
    }
}
