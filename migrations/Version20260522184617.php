<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522184617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_category table (PersonalFinance — per-wallet taxonomy with system + user categories).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_category_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_personal_finance_category (name VARCHAR(120) NOT NULL, is_system BOOLEAN DEFAULT false NOT NULL, system_key VARCHAR(120) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, wallet_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_4346D771A76ED395 ON core_personal_finance_category (user_id)');
        $this->addSql('CREATE INDEX idx_pf_category_wallet ON core_personal_finance_category (wallet_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_category_user_name ON core_personal_finance_category (wallet_id, LOWER(name)) WHERE is_system = false');
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_category_system_key ON core_personal_finance_category (wallet_id, system_key) WHERE system_key IS NOT NULL');
        $this->addSql('ALTER TABLE core_personal_finance_category ADD CONSTRAINT FK_4346D771A76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_category ADD CONSTRAINT FK_4346D771712520F3 FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_category_id CASCADE');
        $this->addSql('ALTER TABLE core_personal_finance_category DROP CONSTRAINT FK_4346D771A76ED395');
        $this->addSql('ALTER TABLE core_personal_finance_category DROP CONSTRAINT FK_4346D771712520F3');
        $this->addSql('DROP TABLE core_personal_finance_category');
    }
}
