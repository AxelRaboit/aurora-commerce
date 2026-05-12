<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512203109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at/updated_at timestamps to core_ged_document_folders and core_ged_document_tags';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_ged_document_folders ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE core_ged_document_folders ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE core_ged_document_folders ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE core_ged_document_folders ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE core_ged_document_tags ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE core_ged_document_tags ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE core_ged_document_tags ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE core_ged_document_tags ALTER updated_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_ged_document_folders DROP created_at');
        $this->addSql('ALTER TABLE core_ged_document_folders DROP updated_at');
        $this->addSql('ALTER TABLE core_ged_document_tags DROP created_at');
        $this->addSql('ALTER TABLE core_ged_document_tags DROP updated_at');
    }
}
