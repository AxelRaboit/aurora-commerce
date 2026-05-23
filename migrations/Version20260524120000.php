<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_categorization_rule (Session 9 — auto-learnt description → category mappings).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_categ_rule_id INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE core_personal_finance_categorization_rule (id INT NOT NULL, user_id INT NOT NULL, category_id INT NOT NULL, pattern VARCHAR(255) NOT NULL, hits INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_categ_rule_user_pattern ON core_personal_finance_categorization_rule (user_id, pattern)');
        $this->addSql('CREATE INDEX idx_pf_categ_rule_pattern ON core_personal_finance_categorization_rule (pattern)');
        $this->addSql('CREATE INDEX idx_pf_categ_rule_category ON core_personal_finance_categorization_rule (category_id)');
        $this->addSql('ALTER TABLE core_personal_finance_categorization_rule ADD CONSTRAINT fk_pf_categ_rule_user FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_categorization_rule ADD CONSTRAINT fk_pf_categ_rule_category FOREIGN KEY (category_id) REFERENCES core_personal_finance_category (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_categorization_rule DROP CONSTRAINT fk_pf_categ_rule_user');
        $this->addSql('ALTER TABLE core_personal_finance_categorization_rule DROP CONSTRAINT fk_pf_categ_rule_category');
        $this->addSql('DROP TABLE core_personal_finance_categorization_rule');
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_categ_rule_id CASCADE');
    }
}
