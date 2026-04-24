<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424220334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts ADD author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_885DBAFAF675F31B ON posts (author_id)');
        $this->addSql('ALTER TABLE users ADD status VARCHAR(20) DEFAULT \'active\' NOT NULL');
        $this->addSql('ALTER TABLE users ADD invitation_selector VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD invitation_hashed_token VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD invitation_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD invited_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9B6869AC0 ON users (invitation_selector)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts DROP CONSTRAINT FK_885DBAFAF675F31B');
        $this->addSql('DROP INDEX IDX_885DBAFAF675F31B');
        $this->addSql('ALTER TABLE posts DROP author_id');
        $this->addSql('DROP INDEX UNIQ_1483A5E9B6869AC0');
        $this->addSql('ALTER TABLE users DROP status');
        $this->addSql('ALTER TABLE users DROP invitation_selector');
        $this->addSql('ALTER TABLE users DROP invitation_hashed_token');
        $this->addSql('ALTER TABLE users DROP invitation_expires_at');
        $this->addSql('ALTER TABLE users DROP invited_at');
    }
}
