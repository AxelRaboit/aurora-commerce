<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename `password_generator.use` → `vault.password_generator.use`.
 *
 * The standalone PasswordGeneratorModule was folded into VaultModule
 * (the password generator is a sub-feature of the Vault section, not
 * a module of its own — both items live under the "Outils" tab). The
 * perm name now follows the Jalon 5 convention `<module_id>.<feature>.<action>`
 * with `vault` as the owner module.
 */
final class Version20260516230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename password_generator.use → vault.password_generator.use in core_users.privileges (after merging PasswordGenerator into VaultModule)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE core_users
             SET privileges = REPLACE(privileges::text, :old, :new)::json
             WHERE privileges::text LIKE :pattern',
            [
                'old' => '"password_generator.use"',
                'new' => '"vault.password_generator.use"',
                'pattern' => '%"password_generator.use"%',
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
                'old' => '"password_generator.use"',
                'new' => '"vault.password_generator.use"',
                'pattern' => '%"vault.password_generator.use"%',
            ],
        );
    }
}
