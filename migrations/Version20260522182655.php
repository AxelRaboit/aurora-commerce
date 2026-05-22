<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522182655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_wallet_member table (PersonalFinance — Wallet sharing pivot).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_wallet_member_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_personal_finance_wallet_member (role VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, wallet_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_C688CA8BA76ED395 ON core_personal_finance_wallet_member (user_id)');
        $this->addSql('CREATE INDEX IDX_C688CA8B712520F3 ON core_personal_finance_wallet_member (wallet_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_wallet_member ON core_personal_finance_wallet_member (wallet_id, user_id)');
        $this->addSql('ALTER TABLE core_personal_finance_wallet_member ADD CONSTRAINT FK_C688CA8BA76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_wallet_member ADD CONSTRAINT FK_C688CA8B712520F3 FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_wallet_member_id CASCADE');
        $this->addSql('ALTER TABLE core_personal_finance_wallet_member DROP CONSTRAINT FK_C688CA8BA76ED395');
        $this->addSql('ALTER TABLE core_personal_finance_wallet_member DROP CONSTRAINT FK_C688CA8B712520F3');
        $this->addSql('DROP TABLE core_personal_finance_wallet_member');
    }
}
