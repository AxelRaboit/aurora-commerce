<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418072433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settings DROP CONSTRAINT settings_pkey');
        $this->addSql('ALTER TABLE settings RENAME COLUMN key TO setting_key');
        $this->addSql('ALTER TABLE settings RENAME COLUMN type TO setting_type');
        $this->addSql('ALTER TABLE settings RENAME COLUMN "group" TO setting_group');
        $this->addSql('ALTER TABLE settings ADD PRIMARY KEY (setting_key)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settings DROP CONSTRAINT settings_pkey');
        $this->addSql('ALTER TABLE settings RENAME COLUMN setting_key TO key');
        $this->addSql('ALTER TABLE settings RENAME COLUMN setting_type TO type');
        $this->addSql('ALTER TABLE settings RENAME COLUMN setting_group TO "group"');
        $this->addSql('ALTER TABLE settings ADD PRIMARY KEY (key)');
    }
}
