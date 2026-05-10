<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260510160209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase core_pdfform_documents.reference from VARCHAR(32) to VARCHAR(64)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_pdfform_documents ALTER COLUMN reference TYPE VARCHAR(64)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_pdfform_documents ALTER COLUMN reference TYPE VARCHAR(32)');
    }
}
