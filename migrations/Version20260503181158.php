<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503181158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoice_lines ADD reference VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoice_lines ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoice_lines ADD discount_cents INT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoice_lines ADD origin VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD discount_rate_bp INT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD reference VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD project VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD incoterms VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD delivery_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD reverse_charge BOOLEAN DEFAULT false');
        $this->addSql('ALTER TABLE billing_invoices ADD bank_details TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_tiers ADD website VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_tiers ADD legal_form VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_tiers ADD bank_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_tiers ADD notes TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoice_lines DROP reference');
        $this->addSql('ALTER TABLE billing_invoice_lines DROP description');
        $this->addSql('ALTER TABLE billing_invoice_lines DROP discount_cents');
        $this->addSql('ALTER TABLE billing_invoice_lines DROP origin');
        $this->addSql('ALTER TABLE billing_invoices DROP discount_rate_bp');
        $this->addSql('ALTER TABLE billing_invoices DROP reference');
        $this->addSql('ALTER TABLE billing_invoices DROP project');
        $this->addSql('ALTER TABLE billing_invoices DROP incoterms');
        $this->addSql('ALTER TABLE billing_invoices DROP delivery_date');
        $this->addSql('ALTER TABLE billing_invoices DROP reverse_charge');
        $this->addSql('ALTER TABLE billing_invoices DROP bank_details');
        $this->addSql('ALTER TABLE billing_tiers DROP website');
        $this->addSql('ALTER TABLE billing_tiers DROP legal_form');
        $this->addSql('ALTER TABLE billing_tiers DROP bank_name');
        $this->addSql('ALTER TABLE billing_tiers DROP notes');
    }
}
