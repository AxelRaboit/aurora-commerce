<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260524210714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'GED Document: add width + height columns (image dimensions)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_ged_documents ADD width INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_ged_documents ADD height INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_ged_documents DROP width');
        $this->addSql('ALTER TABLE core_ged_documents DROP height');
    }
}
