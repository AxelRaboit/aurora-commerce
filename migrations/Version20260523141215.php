<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523141215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PersonalFinance: widen Goal unique index to (user, category, wallet) — allows the cross-wallet variant + per-wallet goals to coexist on the same category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_pf_goal_user_category');
        // COALESCE(wallet_id, 0) so the NULL-wallet slot is treated as
        // a single distinct value (Postgres considers NULLs distinct in
        // standard unique indexes, which would let multiple cross-wallet
        // goals slip through).
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_goal_user_category_wallet ON core_personal_finance_goal (user_id, category_id, COALESCE(wallet_id, 0)) WHERE (category_id IS NOT NULL)');

        // Re-sync every category-tracking goal so saved_amount matches the
        // new policy immediately. Before this migration, a goal with both
        // category AND wallet set summed across every user wallet (the
        // wallet column was decorative); from now on, the same goal scopes
        // to that wallet only. Without this backfill, savedAmount would
        // silently shift the first time any related tx is touched.
        $this->addSql(<<<'SQL'
            UPDATE core_personal_finance_goal g
            SET saved_amount = COALESCE((
                SELECT SUM(t.amount)
                FROM core_personal_finance_transaction t
                WHERE t.user_id = g.user_id
                  AND t.category_id = g.category_id
                  AND t.transfer_id IS NULL
                  AND (g.wallet_id IS NULL OR t.wallet_id = g.wallet_id)
            ), 0)
            WHERE g.category_id IS NOT NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_pf_goal_user_category_wallet');
        $this->addSql('CREATE UNIQUE INDEX uniq_pf_goal_user_category ON core_personal_finance_goal (user_id, category_id) WHERE (category_id IS NOT NULL)');
    }
}
