<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523125256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PersonalFinance: drop orphan unique index uniq_pf_category_user_name on core_personal_finance_category (no longer mapped by Doctrine)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_pf_category_user_name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_category_user_name ON core_personal_finance_category (wallet_id) WHERE (is_system = false)');
    }
}
