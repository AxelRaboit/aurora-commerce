<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503140836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoices ADD credit_note_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_invoices ADD CONSTRAINT FK_72F346691C696F7A FOREIGN KEY (credit_note_id) REFERENCES billing_invoices (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_72F346691C696F7A ON billing_invoices (credit_note_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_invoices DROP CONSTRAINT FK_72F346691C696F7A');
        $this->addSql('DROP INDEX UNIQ_72F346691C696F7A');
        $this->addSql('ALTER TABLE billing_invoices DROP credit_note_id');
    }
}
