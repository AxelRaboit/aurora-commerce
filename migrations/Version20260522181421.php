<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522181421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_wallet table (PersonalFinance module — Wallet sub-feature).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_wallet_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_personal_finance_wallet (name VARCHAR(120) NOT NULL, start_balance NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, mode VARCHAR(16) NOT NULL, show_on_dashboard BOOLEAN DEFAULT true NOT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, owner_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_8EEC5B3D7E3C61F9 ON core_personal_finance_wallet (owner_id)');
        $this->addSql('ALTER TABLE core_personal_finance_wallet ADD CONSTRAINT FK_8EEC5B3D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER INDEX idx_assistconv_agency RENAME TO IDX_C2DBED58CDEADB2A');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_wallet_id CASCADE');
        $this->addSql('ALTER TABLE core_personal_finance_wallet DROP CONSTRAINT FK_8EEC5B3D7E3C61F9');
        $this->addSql('DROP TABLE core_personal_finance_wallet');
        $this->addSql('ALTER INDEX idx_c2dbed58cdeadb2a RENAME TO idx_assistconv_agency');
    }
}
