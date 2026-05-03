<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503214257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE access_requests ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_16901760AEA34913 ON access_requests (reference)');
        $this->addSql('ALTER TABLE comments ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F9E962AAEA34913 ON comments (reference)');
        $this->addSql('ALTER TABLE form_submissions ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C80AF9E6AEA34913 ON form_submissions (reference)');
        $this->addSql('ALTER TABLE media ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2CA10CAEA34913 ON media (reference)');
        $this->addSql('ALTER TABLE photo_gallery_invites ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2EC6357BAEA34913 ON photo_gallery_invites (reference)');
        $this->addSql('ALTER TABLE photo_gallery_items ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_81B213B9AEA34913 ON photo_gallery_items (reference)');
        $this->addSql('ALTER TABLE users ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9AEA34913 ON users (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_16901760AEA34913');
        $this->addSql('ALTER TABLE access_requests DROP reference');
        $this->addSql('DROP INDEX UNIQ_5F9E962AAEA34913');
        $this->addSql('ALTER TABLE comments DROP reference');
        $this->addSql('DROP INDEX UNIQ_C80AF9E6AEA34913');
        $this->addSql('ALTER TABLE form_submissions DROP reference');
        $this->addSql('DROP INDEX UNIQ_6A2CA10CAEA34913');
        $this->addSql('ALTER TABLE media DROP reference');
        $this->addSql('DROP INDEX UNIQ_2EC6357BAEA34913');
        $this->addSql('ALTER TABLE photo_gallery_invites DROP reference');
        $this->addSql('DROP INDEX UNIQ_81B213B9AEA34913');
        $this->addSql('ALTER TABLE photo_gallery_items DROP reference');
        $this->addSql('DROP INDEX UNIQ_1483A5E9AEA34913');
        $this->addSql('ALTER TABLE users DROP reference');
    }
}
