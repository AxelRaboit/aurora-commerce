<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260429211254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo_galleries ADD client_contact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE photo_galleries ADD CONSTRAINT FK_FAF0389677F5180B FOREIGN KEY (client_contact_id) REFERENCES crm_contacts (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_FAF0389677F5180B ON photo_galleries (client_contact_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo_galleries DROP CONSTRAINT FK_FAF0389677F5180B');
        $this->addSql('DROP INDEX IDX_FAF0389677F5180B');
        $this->addSql('ALTER TABLE photo_galleries DROP client_contact_id');
    }
}
