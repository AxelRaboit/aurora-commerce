<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523145708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PersonalFinance: drop AbsoluteSum tracking mode from Goal. Existing absolute_sum rows are converted to expense_only — manual goals (category=null) are unaffected since the mode is ignored without a category; category-tracking goals on the legacy mode are flipped to the new default and will recompute on the next tx event.';
    }

    public function up(Schema $schema): void
    {
        // Note: the diff-generated DROP/RECREATE of
        // `uniq_pf_goal_user_category_wallet` is a false positive — the
        // index uses COALESCE(wallet_id, 0) which Doctrine's schema
        // comparator can't read. Index is fine, skip the noise.
        $this->addSql("UPDATE core_personal_finance_goal SET tracking_mode = 'expense_only' WHERE tracking_mode = 'absolute_sum'");
    }

    public function down(Schema $schema): void
    {
        // Down is a no-op — the absolute_sum value disappears from the
        // PHP enum, so reverting the data wouldn't help (Doctrine would
        // reject the unknown enum value on read anyway).
    }
}
