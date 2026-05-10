<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260510190623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PdfDocument: replace file_id (Media FK) with file_path (var/pdfform/ relative path)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_pdfform_documents DROP CONSTRAINT IF EXISTS fk_25ecd2a093cb796c');
        $this->addSql('DROP INDEX IF EXISTS idx_25ecd2a093cb796c');
        $this->addSql('ALTER TABLE core_pdfform_documents DROP COLUMN IF EXISTS file_id');
        $this->addSql('ALTER TABLE core_pdfform_documents ADD COLUMN IF NOT EXISTS file_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_pdfform_documents DROP COLUMN IF EXISTS file_path');
        $this->addSql('ALTER TABLE core_pdfform_documents ADD COLUMN IF NOT EXISTS file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_pdfform_documents ADD CONSTRAINT fk_25ecd2a093cb796c FOREIGN KEY (file_id) REFERENCES core_media (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_25ecd2a093cb796c ON core_pdfform_documents (file_id)');
    }
}
