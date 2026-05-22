<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522185936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_transaction table (PersonalFinance — core entity recording income/expense).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_transaction_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_personal_finance_transaction (type VARCHAR(16) NOT NULL, amount NUMERIC(10, 2) NOT NULL, description VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, tags JSON DEFAULT NULL, transfer_id VARCHAR(36) DEFAULT NULL, split_id VARCHAR(36) DEFAULT NULL, attachment_path VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, wallet_id INT NOT NULL, category_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2C5E85AA76ED395 ON core_personal_finance_transaction (user_id)');
        $this->addSql('CREATE INDEX IDX_2C5E85A712520F3 ON core_personal_finance_transaction (wallet_id)');
        $this->addSql('CREATE INDEX IDX_2C5E85A12469DE2 ON core_personal_finance_transaction (category_id)');
        $this->addSql('CREATE INDEX idx_pf_transaction_wallet_date ON core_personal_finance_transaction (wallet_id, date)');
        $this->addSql('CREATE INDEX idx_pf_transaction_user_date ON core_personal_finance_transaction (user_id, date)');
        $this->addSql('CREATE INDEX idx_pf_transaction_category_date ON core_personal_finance_transaction (category_id, date)');
        $this->addSql('CREATE INDEX idx_pf_transaction_transfer ON core_personal_finance_transaction (transfer_id)');
        $this->addSql('CREATE INDEX idx_pf_transaction_split ON core_personal_finance_transaction (split_id)');
        $this->addSql('ALTER TABLE core_personal_finance_transaction ADD CONSTRAINT FK_2C5E85AA76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_transaction ADD CONSTRAINT FK_2C5E85A712520F3 FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_transaction ADD CONSTRAINT FK_2C5E85A12469DE2 FOREIGN KEY (category_id) REFERENCES core_personal_finance_category (id) ON DELETE SET NULL NOT DEFERRABLE');
        // Note: Doctrine diff tried to DROP uniq_pf_category_system_key and uniq_pf_category_user_name
        // (partial indexes added manually in Version20260522184617) because the ORM mapping does not
        // declare them. Removed those DROPs from this migration; they should NOT be dropped — they enforce
        // category uniqueness rules at the DB level. Future migration:diff will keep proposing them — ignore.
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_transaction_id CASCADE');
        $this->addSql('ALTER TABLE core_personal_finance_transaction DROP CONSTRAINT FK_2C5E85AA76ED395');
        $this->addSql('ALTER TABLE core_personal_finance_transaction DROP CONSTRAINT FK_2C5E85A712520F3');
        $this->addSql('ALTER TABLE core_personal_finance_transaction DROP CONSTRAINT FK_2C5E85A12469DE2');
        $this->addSql('DROP TABLE core_personal_finance_transaction');
    }
}
