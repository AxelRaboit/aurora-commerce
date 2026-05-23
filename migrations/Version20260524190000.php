<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename the `bills` budget section to `fixed_charges`. The user-facing
 * label was confusing in French ("Factures" suggested incoming invoices
 * to pay rather than recurring fixed charges like rent + subscriptions),
 * so the enum case was renamed for clarity across both languages.
 *
 * Touches the `section` text column on budget items only — no schema
 * change, just a data update so the column matches the new enum value.
 */
final class Version20260524190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename budget section `bills` to `fixed_charges` in budget item rows.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE core_personal_finance_budget_item SET section = 'fixed_charges' WHERE section = 'bills'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE core_personal_finance_budget_item SET section = 'bills' WHERE section = 'fixed_charges'");
    }
}
