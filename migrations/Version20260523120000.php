<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_budget + core_personal_finance_budget_item (Session 6a — monthly budgets with section-grouped line items).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_budget_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_budget_item_id INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE core_personal_finance_budget (id INT NOT NULL, user_id INT NOT NULL, wallet_id INT NOT NULL, month DATE NOT NULL, notes VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_budget_wallet_month ON core_personal_finance_budget (wallet_id, month)');
        $this->addSql('CREATE INDEX idx_pf_budget_user ON core_personal_finance_budget (user_id)');
        $this->addSql('ALTER TABLE core_personal_finance_budget ADD CONSTRAINT fk_pf_budget_user FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_budget ADD CONSTRAINT fk_pf_budget_wallet FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE CASCADE NOT DEFERRABLE');

        $this->addSql('CREATE TABLE core_personal_finance_budget_item (id INT NOT NULL, budget_id INT NOT NULL, category_id INT DEFAULT NULL, section VARCHAR(16) NOT NULL, label VARCHAR(120) NOT NULL, planned_amount NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, carried_over NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, position INT DEFAULT 0 NOT NULL, notes VARCHAR(255) DEFAULT NULL, repeat_next_month BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_pf_budget_item_budget ON core_personal_finance_budget_item (budget_id)');
        $this->addSql('CREATE INDEX idx_pf_budget_item_category ON core_personal_finance_budget_item (category_id)');
        $this->addSql('ALTER TABLE core_personal_finance_budget_item ADD CONSTRAINT fk_pf_budget_item_budget FOREIGN KEY (budget_id) REFERENCES core_personal_finance_budget (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_budget_item ADD CONSTRAINT fk_pf_budget_item_category FOREIGN KEY (category_id) REFERENCES core_personal_finance_category (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_budget_item DROP CONSTRAINT fk_pf_budget_item_budget');
        $this->addSql('ALTER TABLE core_personal_finance_budget_item DROP CONSTRAINT fk_pf_budget_item_category');
        $this->addSql('DROP TABLE core_personal_finance_budget_item');
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_budget_item_id CASCADE');

        $this->addSql('ALTER TABLE core_personal_finance_budget DROP CONSTRAINT fk_pf_budget_user');
        $this->addSql('ALTER TABLE core_personal_finance_budget DROP CONSTRAINT fk_pf_budget_wallet');
        $this->addSql('DROP TABLE core_personal_finance_budget');
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_budget_id CASCADE');
    }
}
