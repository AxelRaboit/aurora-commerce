<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503203310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split invoice number into internal number (sequential) and supplier number (from document)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billing_invoices ADD supplier_number VARCHAR(64) DEFAULT NULL');
        // Move existing numbers (which came from OCR/supplier documents) to supplier_number
        $this->addSql('UPDATE billing_invoices SET supplier_number = number WHERE number IS NOT NULL');
        // Clear number so internal sequential numbers will be generated on next validation
        // Exception: already-validated invoices get a new FAC- number to preserve compliance
        $this->addSql("UPDATE billing_invoices SET number = NULL WHERE status IN ('needs_review', 'draft')");
        $this->addSql("UPDATE billing_invoices SET number = CONCAT('FAC-', EXTRACT(YEAR FROM COALESCE(issued_at, NOW()))::int, '-', LPAD(id::text, 4, '0')) WHERE status NOT IN ('needs_review', 'draft') AND number IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoices DROP supplier_number');
    }
}
