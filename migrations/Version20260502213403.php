<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502213403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend billing invoices/lines with extra extraction fields (SKU, unit, gross totals, PO ref, payment terms/method)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billing_invoice_lines ADD sku VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoice_lines ADD unit VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoice_lines ADD total_gross_cents INT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD purchase_order_ref VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD payment_terms VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD payment_method VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billing_invoice_lines DROP sku');
        $this->addSql('ALTER TABLE billing_invoice_lines DROP unit');
        $this->addSql('ALTER TABLE billing_invoice_lines DROP total_gross_cents');
        $this->addSql('ALTER TABLE billing_invoices DROP purchase_order_ref');
        $this->addSql('ALTER TABLE billing_invoices DROP payment_terms');
        $this->addSql('ALTER TABLE billing_invoices DROP payment_method');
    }
}
