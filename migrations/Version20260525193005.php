<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525193005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add core_media_versions table for the Media library file-version history.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE seq_core_media_version_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_media_versions (path VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, version_number INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, note VARCHAR(255) DEFAULT NULL, id INT NOT NULL, media_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_EDE187EFEA9FDD75 ON core_media_versions (media_id)');
        $this->addSql('ALTER TABLE core_media_versions ADD CONSTRAINT FK_EDE187EFEA9FDD75 FOREIGN KEY (media_id) REFERENCES core_media (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_media_version_id CASCADE');
        $this->addSql('ALTER TABLE core_media_versions DROP CONSTRAINT FK_EDE187EFEA9FDD75');
        $this->addSql('DROP TABLE core_media_versions');
    }
}
