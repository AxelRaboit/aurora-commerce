<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_recurring_tx + core_personal_finance_scheduled_tx (Session 8 — monthly recurring + one-off scheduled transaction rules).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_recurring_tx_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_scheduled_tx_id INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE core_personal_finance_recurring_tx (id INT NOT NULL, user_id INT NOT NULL, wallet_id INT NOT NULL, category_id INT DEFAULT NULL, type VARCHAR(16) NOT NULL, amount NUMERIC(10, 2) NOT NULL, description VARCHAR(255) DEFAULT NULL, day_of_month SMALLINT NOT NULL, active BOOLEAN DEFAULT true NOT NULL, last_generated_at DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_pf_recurring_user ON core_personal_finance_recurring_tx (user_id)');
        $this->addSql('CREATE INDEX idx_pf_recurring_wallet ON core_personal_finance_recurring_tx (wallet_id)');
        $this->addSql('CREATE INDEX idx_pf_recurring_active_day ON core_personal_finance_recurring_tx (active, day_of_month)');
        $this->addSql('ALTER TABLE core_personal_finance_recurring_tx ADD CONSTRAINT chk_pf_recurring_day_range CHECK (day_of_month BETWEEN 1 AND 28)');
        $this->addSql('ALTER TABLE core_personal_finance_recurring_tx ADD CONSTRAINT fk_pf_recurring_user FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_recurring_tx ADD CONSTRAINT fk_pf_recurring_wallet FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_recurring_tx ADD CONSTRAINT fk_pf_recurring_category FOREIGN KEY (category_id) REFERENCES core_personal_finance_category (id) ON DELETE SET NULL NOT DEFERRABLE');

        $this->addSql('CREATE TABLE core_personal_finance_scheduled_tx (id INT NOT NULL, user_id INT NOT NULL, wallet_id INT NOT NULL, category_id INT DEFAULT NULL, type VARCHAR(16) NOT NULL, amount NUMERIC(10, 2) NOT NULL, description VARCHAR(255) DEFAULT NULL, scheduled_date DATE NOT NULL, generated BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_pf_scheduled_user ON core_personal_finance_scheduled_tx (user_id)');
        $this->addSql('CREATE INDEX idx_pf_scheduled_wallet ON core_personal_finance_scheduled_tx (wallet_id)');
        $this->addSql('CREATE INDEX idx_pf_scheduled_date ON core_personal_finance_scheduled_tx (scheduled_date)');
        $this->addSql('ALTER TABLE core_personal_finance_scheduled_tx ADD CONSTRAINT fk_pf_scheduled_user FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_scheduled_tx ADD CONSTRAINT fk_pf_scheduled_wallet FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_scheduled_tx ADD CONSTRAINT fk_pf_scheduled_category FOREIGN KEY (category_id) REFERENCES core_personal_finance_category (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_scheduled_tx DROP CONSTRAINT fk_pf_scheduled_user');
        $this->addSql('ALTER TABLE core_personal_finance_scheduled_tx DROP CONSTRAINT fk_pf_scheduled_wallet');
        $this->addSql('ALTER TABLE core_personal_finance_scheduled_tx DROP CONSTRAINT fk_pf_scheduled_category');
        $this->addSql('DROP TABLE core_personal_finance_scheduled_tx');
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_scheduled_tx_id CASCADE');

        $this->addSql('ALTER TABLE core_personal_finance_recurring_tx DROP CONSTRAINT fk_pf_recurring_user');
        $this->addSql('ALTER TABLE core_personal_finance_recurring_tx DROP CONSTRAINT fk_pf_recurring_wallet');
        $this->addSql('ALTER TABLE core_personal_finance_recurring_tx DROP CONSTRAINT fk_pf_recurring_category');
        $this->addSql('DROP TABLE core_personal_finance_recurring_tx');
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_recurring_tx_id CASCADE');
    }
}
