<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505235207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE seq_agency_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_service_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<'SQL'
                CREATE TABLE agencies (
                  id INT NOT NULL,
                  name VARCHAR(150) NOT NULL,
                  created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                  updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                  PRIMARY KEY (id)
                )
            SQL);
        $this->addSql(<<<'SQL'
                CREATE TABLE services (
                  id INT NOT NULL,
                  name VARCHAR(150) NOT NULL,
                  created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                  updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                  PRIMARY KEY (id)
                )
            SQL);
        $this->addSql('ALTER TABLE users ADD agency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD service_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
                ALTER TABLE
                  users
                ADD
                  CONSTRAINT FK_1483A5E9CDEADB2A FOREIGN KEY (agency_id) REFERENCES agencies (id) ON DELETE
                SET
                  NULL NOT DEFERRABLE
            SQL);
        $this->addSql(<<<'SQL'
                ALTER TABLE
                  users
                ADD
                  CONSTRAINT FK_1483A5E9ED5CA9E6 FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE
                SET
                  NULL NOT DEFERRABLE
            SQL);
        $this->addSql('CREATE INDEX IDX_1483A5E9CDEADB2A ON users (agency_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9ED5CA9E6 ON users (service_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_agency_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_service_id CASCADE');
        $this->addSql('DROP TABLE agencies');
        $this->addSql('DROP TABLE services');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9CDEADB2A');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9ED5CA9E6');
        $this->addSql('DROP INDEX IDX_1483A5E9CDEADB2A');
        $this->addSql('DROP INDEX IDX_1483A5E9ED5CA9E6');
        $this->addSql('ALTER TABLE users DROP agency_id');
        $this->addSql('ALTER TABLE users DROP service_id');
    }
}
