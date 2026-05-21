<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521183427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add width/height columns to core_post_it_notes for resizable post-its.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_post_it_notes ADD width INT DEFAULT 220 NOT NULL');
        $this->addSql('ALTER TABLE core_post_it_notes ADD height INT DEFAULT 220 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_post_it_notes DROP width');
        $this->addSql('ALTER TABLE core_post_it_notes DROP height');
    }
}
