<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523142453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PersonalFinance: add Goal.tracking_mode (income_only|expense_only|absolute_sum). New goals default to expense_only; existing category-tracking goals are backfilled to absolute_sum to preserve their saved_amount.';
    }

    public function up(Schema $schema): void
    {
        // Note: the DROP of `uniq_pf_goal_user_category_wallet` the diff
        // generator suggests is a false positive — Doctrine's schema
        // comparator can't read the COALESCE expression in the index,
        // so it thinks the index needs recreating. The index is fine,
        // skip the drop/recreate.
        $this->addSql("ALTER TABLE core_personal_finance_goal ADD tracking_mode VARCHAR(16) DEFAULT 'expense_only' NOT NULL");

        // Backfill: existing category-tracking goals keep the legacy
        // Spendly-compatible "absolute sum" semantic so their
        // saved_amount stays put. New goals from the form default to
        // expense_only (the column default).
        $this->addSql("UPDATE core_personal_finance_goal SET tracking_mode = 'absolute_sum' WHERE category_id IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_goal DROP tracking_mode');
    }
}
