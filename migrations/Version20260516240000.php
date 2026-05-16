<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename `platform.users.modules.manage` → `platform.users.module_access.manage`.
 *
 * The legacy name read ambiguously: `users.modules.manage` looks like
 * "manage [users.modules]" but the actual semantic is "manage [the
 * modules accessible by a user]" — i.e. a User entity's
 * `disabled_modules` JSON column. Rename to `users.module_access.manage`
 * to disambiguate.
 *
 * Affects core_users.privileges (JSON array of granted privilege names).
 */
final class Version20260516240000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename platform.users.modules.manage → platform.users.module_access.manage in core_users.privileges';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE core_users
             SET privileges = REPLACE(privileges::text, :old, :new)::json
             WHERE privileges::text LIKE :pattern',
            [
                'old' => '"platform.users.modules.manage"',
                'new' => '"platform.users.module_access.manage"',
                'pattern' => '%"platform.users.modules.manage"%',
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE core_users
             SET privileges = REPLACE(privileges::text, :new, :old)::json
             WHERE privileges::text LIKE :pattern',
            [
                'old' => '"platform.users.modules.manage"',
                'new' => '"platform.users.module_access.manage"',
                'pattern' => '%"platform.users.module_access.manage"%',
            ],
        );
    }
}
