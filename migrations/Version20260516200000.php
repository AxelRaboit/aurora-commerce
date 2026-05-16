<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Jalon 5.1 — relocate `platform.search.view` to `general.search.view`.
 *
 * Global search lives in the backend header — it's omnipresent infra,
 * not Platform-specific (Users/Agencies/Services don't depend on it,
 * and Platform has no NavItem that uses it). Better home: GeneralModule,
 * alongside `general.dashboard.view`, both of which are "general-purpose
 * backend infra" rather than a CRUD admin tab.
 */
final class Version20260516200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Jalon 5.1 — rename platform.search.view → general.search.view (search moves to GeneralModule)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE core_users SET privileges = REPLACE(privileges::text, :old, :new)::jsonb WHERE privileges::text LIKE :pattern',
            ['old' => '"platform.search.view"', 'new' => '"general.search.view"', 'pattern' => '%"platform.search.view"%'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE core_users SET privileges = REPLACE(privileges::text, :new, :old)::jsonb WHERE privileges::text LIKE :pattern',
            ['old' => '"platform.search.view"', 'new' => '"general.search.view"', 'pattern' => '%"general.search.view"%'],
        );
    }
}
