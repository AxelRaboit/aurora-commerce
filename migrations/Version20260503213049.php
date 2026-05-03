<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503213049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_tiers ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_13381E8FAEA34913 ON billing_tiers (reference)');
        $this->addSql('ALTER TABLE forms ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FD3F1BF7AEA34913 ON forms (reference)');
        $this->addSql('ALTER TABLE photo_galleries ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FAF03896AEA34913 ON photo_galleries (reference)');
        $this->addSql('ALTER TABLE posts ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_885DBAFAAEA34913 ON posts (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_13381E8FAEA34913');
        $this->addSql('ALTER TABLE billing_tiers DROP reference');
        $this->addSql('DROP INDEX UNIQ_FD3F1BF7AEA34913');
        $this->addSql('ALTER TABLE forms DROP reference');
        $this->addSql('DROP INDEX UNIQ_FAF03896AEA34913');
        $this->addSql('ALTER TABLE photo_galleries DROP reference');
        $this->addSql('DROP INDEX UNIQ_885DBAFAAEA34913');
        $this->addSql('ALTER TABLE posts DROP reference');
    }
}
