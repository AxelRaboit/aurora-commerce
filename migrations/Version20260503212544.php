<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503212544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crm_companies ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C32E5717AEA34913 ON crm_companies (reference)');
        $this->addSql('ALTER TABLE crm_contacts ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5DC758D8AEA34913 ON crm_contacts (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_C32E5717AEA34913');
        $this->addSql('ALTER TABLE crm_companies DROP reference');
        $this->addSql('DROP INDEX UNIQ_5DC758D8AEA34913');
        $this->addSql('ALTER TABLE crm_contacts DROP reference');
    }
}
