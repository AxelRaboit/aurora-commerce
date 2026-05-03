<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503180430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoices ADD discount_cents INT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD freight_cents INT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD insurance_cents INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoices DROP discount_cents');
        $this->addSql('ALTER TABLE billing_invoices DROP freight_cents');
        $this->addSql('ALTER TABLE billing_invoices DROP insurance_cents');
    }
}
