<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260530140203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Soft-reference Photo Gallery→CRM Contact (drop cross-module FK)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_photo_galleries DROP CONSTRAINT fk_870cface77f5180b');
        $this->addSql('DROP INDEX idx_870cface77f5180b');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_photo_galleries ADD CONSTRAINT fk_870cface77f5180b FOREIGN KEY (client_contact_id) REFERENCES core_crm_contacts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_870cface77f5180b ON core_photo_galleries (client_contact_id)');
    }
}
