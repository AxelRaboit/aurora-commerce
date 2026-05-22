<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522183439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_personal_finance_wallet_invitation table (PersonalFinance — Wallet sharing invitations).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_personal_finance_wallet_invitation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_personal_finance_wallet_invitation (email VARCHAR(180) NOT NULL, role VARCHAR(16) NOT NULL, token VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, declined_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, invited_by_id INT NOT NULL, wallet_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AAEF0F695F37A13B ON core_personal_finance_wallet_invitation (token)');
        $this->addSql('CREATE INDEX IDX_AAEF0F69A7B4A7E3 ON core_personal_finance_wallet_invitation (invited_by_id)');
        $this->addSql('CREATE INDEX IDX_AAEF0F69712520F3 ON core_personal_finance_wallet_invitation (wallet_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_wallet_invitation_email ON core_personal_finance_wallet_invitation (wallet_id, email)');
        $this->addSql('ALTER TABLE core_personal_finance_wallet_invitation ADD CONSTRAINT FK_AAEF0F69A7B4A7E3 FOREIGN KEY (invited_by_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_personal_finance_wallet_invitation ADD CONSTRAINT FK_AAEF0F69712520F3 FOREIGN KEY (wallet_id) REFERENCES core_personal_finance_wallet (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE seq_core_personal_finance_wallet_invitation_id CASCADE');
        $this->addSql('ALTER TABLE core_personal_finance_wallet_invitation DROP CONSTRAINT FK_AAEF0F69A7B4A7E3');
        $this->addSql('ALTER TABLE core_personal_finance_wallet_invitation DROP CONSTRAINT FK_AAEF0F69712520F3');
        $this->addSql('DROP TABLE core_personal_finance_wallet_invitation');
    }
}
