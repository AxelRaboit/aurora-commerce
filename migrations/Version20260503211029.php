<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503211029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crm_deals ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5330DBA3AEA34913 ON crm_deals (reference)');
        $this->addSql('ALTER TABLE ecommerce_listings ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10E893BAEA34913 ON ecommerce_listings (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_5330DBA3AEA34913');
        $this->addSql('ALTER TABLE crm_deals DROP reference');
        $this->addSql('DROP INDEX UNIQ_10E893BAEA34913');
        $this->addSql('ALTER TABLE ecommerce_listings DROP reference');
    }
}
