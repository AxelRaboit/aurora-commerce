<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_goal (Session 7 — savings goals, optionally auto-tracked from a category).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_goal_id INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE core_personal_finance_goal (id INT NOT NULL, user_id INT NOT NULL, wallet_id INT DEFAULT NULL, category_id INT DEFAULT NULL, name VARCHAR(120) NOT NULL, target_amount NUMERIC(10, 2) NOT NULL, saved_amount NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, deadline DATE DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_pf_goal_user ON core_personal_finance_goal (user_id)');
        $this->addSql('CREATE INDEX idx_pf_goal_category ON core_personal_finance_goal (category_id)');
        $this->addSql('CREATE INDEX idx_pf_goal_wallet ON core_personal_finance_goal (wallet_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_goal_user_category ON core_personal_finance_goal (user_id, category_id) WHERE (category_id IS NOT NULL)');
        $this->addSql('ALTER TABLE core_personal_finance_goal ADD CONSTRAINT fk_pf_goal_user FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_goal ADD CONSTRAINT fk_pf_goal_wallet FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_goal ADD CONSTRAINT fk_pf_goal_category FOREIGN KEY (category_id) REFERENCES core_personal_finance_category (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_goal DROP CONSTRAINT fk_pf_goal_user');
        $this->addSql('ALTER TABLE core_personal_finance_goal DROP CONSTRAINT fk_pf_goal_wallet');
        $this->addSql('ALTER TABLE core_personal_finance_goal DROP CONSTRAINT fk_pf_goal_category');
        $this->addSql('DROP TABLE core_personal_finance_goal');
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_goal_id CASCADE');
    }
}
