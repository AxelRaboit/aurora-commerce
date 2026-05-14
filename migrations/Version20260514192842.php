<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the join table linking contacts to contact tags. The legacy JSON
 * tags column on core_crm_contacts is preserved here so that the next
 * migration can read its values and back-fill ContactTag rows + join rows
 * before dropping the column.
 */
final class Version20260514192842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_crm_contact_tag_map join table for the Contact <-> ContactTag ManyToMany relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE core_crm_contact_tag_map (contact_id INT NOT NULL, contact_tag_id INT NOT NULL, PRIMARY KEY (contact_id, contact_tag_id))');
        $this->addSql('CREATE INDEX IDX_2C26A5FFE7A1254A ON core_crm_contact_tag_map (contact_id)');
        $this->addSql('CREATE INDEX IDX_2C26A5FF2A405490 ON core_crm_contact_tag_map (contact_tag_id)');
        $this->addSql('ALTER TABLE core_crm_contact_tag_map ADD CONSTRAINT FK_2C26A5FFE7A1254A FOREIGN KEY (contact_id) REFERENCES core_crm_contacts (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_crm_contact_tag_map ADD CONSTRAINT FK_2C26A5FF2A405490 FOREIGN KEY (contact_tag_id) REFERENCES core_crm_contact_tags (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_crm_contact_tag_map DROP CONSTRAINT FK_2C26A5FFE7A1254A');
        $this->addSql('ALTER TABLE core_crm_contact_tag_map DROP CONSTRAINT FK_2C26A5FF2A405490');
        $this->addSql('DROP TABLE core_crm_contact_tag_map');
    }
}
