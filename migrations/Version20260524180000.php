<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V1 cleanup: align Recurring/Scheduled/Categorization table + sequence
 * names with the `core_personal_finance_<snake_entity>` /
 * `seq_core_personal_finance_<snake_entity>_id` convention. The
 * shortened `_tx` / `_categ_` aliases used in their first migrations
 * deviated from the rule documented in
 * `entity_extensibility_convention.md` §"Couche 1 — Entity".
 *
 * Tables are renamed in place (preserves data) — only used in dev
 * environments before V1 ships, but ALTER ... RENAME is safe under
 * load too.
 */
final class Version20260524180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V1 cleanup: rename recurring/scheduled tables + 3 sequences to the canonical seq_core_<snake_entity>_id convention.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_recurring_tx RENAME TO core_personal_finance_recurring_transaction');
        $this->addSql('ALTER TABLE core_personal_finance_scheduled_tx RENAME TO core_personal_finance_scheduled_transaction');

        $this->addSql('ALTER SEQUENCE seq_core_personal_finance_recurring_tx_id RENAME TO seq_core_personal_finance_recurring_transaction_id');
        $this->addSql('ALTER SEQUENCE seq_core_personal_finance_scheduled_tx_id RENAME TO seq_core_personal_finance_scheduled_transaction_id');
        $this->addSql('ALTER SEQUENCE seq_core_personal_finance_categ_rule_id RENAME TO seq_core_personal_finance_categorization_rule_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER SEQUENCE seq_core_personal_finance_categorization_rule_id RENAME TO seq_core_personal_finance_categ_rule_id');
        $this->addSql('ALTER SEQUENCE seq_core_personal_finance_scheduled_transaction_id RENAME TO seq_core_personal_finance_scheduled_tx_id');
        $this->addSql('ALTER SEQUENCE seq_core_personal_finance_recurring_transaction_id RENAME TO seq_core_personal_finance_recurring_tx_id');

        $this->addSql('ALTER TABLE core_personal_finance_scheduled_transaction RENAME TO core_personal_finance_scheduled_tx');
        $this->addSql('ALTER TABLE core_personal_finance_recurring_transaction RENAME TO core_personal_finance_recurring_tx');
    }
}
