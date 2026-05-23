<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523135604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PersonalFinance: track explicit budget rollover (rolled_over_at) — replaces silent auto-rollover from ensureForMonth';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_budget ADD rolled_over_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        // Existing budgets created via the auto-rollover policy are
        // treated as already-rolled-over so the banner doesn't appear
        // retroactively on months the user already moved past.
        $this->addSql("UPDATE core_personal_finance_budget SET rolled_over_at = created_at WHERE EXISTS (SELECT 1 FROM core_personal_finance_budget_item WHERE budget_id = core_personal_finance_budget.id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_budget DROP rolled_over_at');
    }
}
