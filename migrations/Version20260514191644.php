<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514191644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE seq_core_contact_tag_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_crm_contact_tags (label VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL, color VARCHAR(7) DEFAULT \'#6366F1\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1E6EF199EA750E8 ON core_crm_contact_tags (label)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1E6EF199989D9B62 ON core_crm_contact_tags (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_contact_tag_id CASCADE');
        $this->addSql('DROP TABLE core_crm_contact_tags');
    }
}
