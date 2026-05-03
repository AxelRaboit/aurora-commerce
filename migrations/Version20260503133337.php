<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503133337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoices ADD buyer_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD buyer_vat_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD buyer_address TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD buyer_country_code VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER locale DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoices DROP buyer_name');
        $this->addSql('ALTER TABLE billing_invoices DROP buyer_vat_number');
        $this->addSql('ALTER TABLE billing_invoices DROP buyer_address');
        $this->addSql('ALTER TABLE billing_invoices DROP buyer_country_code');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER locale SET DEFAULT \'fr\'');
    }
}
