<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523114826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PersonalFinance: add BudgetPreset + BudgetPresetItem tables (v2-1B reusable month templates per wallet)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_budget_preset_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_budget_preset_item_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_personal_finance_budget_preset (name VARCHAR(120) NOT NULL, description VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, wallet_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_pf_budget_preset_user ON core_personal_finance_budget_preset (user_id)');
        $this->addSql('CREATE INDEX idx_pf_budget_preset_wallet ON core_personal_finance_budget_preset (wallet_id)');
        $this->addSql('CREATE TABLE core_personal_finance_budget_preset_item (section VARCHAR(16) NOT NULL, label VARCHAR(120) NOT NULL, planned_amount NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, position INT DEFAULT 0 NOT NULL, notes VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, preset_id INT NOT NULL, category_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9119390512469DE2 ON core_personal_finance_budget_preset_item (category_id)');
        $this->addSql('CREATE INDEX idx_pf_budget_preset_item_preset ON core_personal_finance_budget_preset_item (preset_id)');
        $this->addSql('ALTER TABLE core_personal_finance_budget_preset ADD CONSTRAINT FK_7E260D39A76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_budget_preset ADD CONSTRAINT FK_7E260D39712520F3 FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_budget_preset_item ADD CONSTRAINT FK_9119390580688E6F FOREIGN KEY (preset_id) REFERENCES core_personal_finance_budget_preset (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_budget_preset_item ADD CONSTRAINT FK_9119390512469DE2 FOREIGN KEY (category_id) REFERENCES core_personal_finance_category (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_budget_preset_item DROP CONSTRAINT FK_9119390580688E6F');
        $this->addSql('ALTER TABLE core_personal_finance_budget_preset_item DROP CONSTRAINT FK_9119390512469DE2');
        $this->addSql('ALTER TABLE core_personal_finance_budget_preset DROP CONSTRAINT FK_7E260D39A76ED395');
        $this->addSql('ALTER TABLE core_personal_finance_budget_preset DROP CONSTRAINT FK_7E260D39712520F3');
        $this->addSql('DROP TABLE core_personal_finance_budget_preset_item');
        $this->addSql('DROP TABLE core_personal_finance_budget_preset');
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_budget_preset_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_budget_preset_item_id CASCADE');
    }
}
