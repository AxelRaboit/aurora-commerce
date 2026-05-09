<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509123750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE seq_core_mount_point_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_mount_points (name VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, host VARCHAR(255) NOT NULL, port INT DEFAULT NULL, username VARCHAR(100) DEFAULT NULL, password TEXT DEFAULT NULL, database VARCHAR(100) DEFAULT NULL, ssh_public_key TEXT DEFAULT NULL, config JSON NOT NULL, last_tested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_test_successful BOOLEAN DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_mount_point_id CASCADE');
        $this->addSql('DROP TABLE core_mount_points');
    }
}
