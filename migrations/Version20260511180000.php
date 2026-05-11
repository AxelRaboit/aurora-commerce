<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Harmonize four ModuleParameterEnum setting keys to the consistent
 * `backend_<module>_<sub>` shape. Touches:
 *
 *   `backend_ecommerce_front` → `backend_ecommerce_frontend`
 *   `photo_front`             → `backend_photo_frontend`
 *   `front_editorial`         → `backend_editorial_frontend`
 *   `photo_admin`             → `backend_photo_admin`
 *
 * Updates both the `core_settings` table (parameter keys) and the JSON
 * `disabled_modules` column of `core_users` (per-user mask).
 */
final class Version20260511180000 extends AbstractMigration
{
    private const array RENAMES = [
        'backend_ecommerce_front' => 'backend_ecommerce_frontend',
        'photo_front' => 'backend_photo_frontend',
        'front_editorial' => 'backend_editorial_frontend',
        'photo_admin' => 'backend_photo_admin',
    ];

    public function getDescription(): string
    {
        return 'Rename front-toggle setting keys to backend_<module>_frontend shape';
    }

    public function up(Schema $schema): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->addSql(
                'UPDATE core_settings SET setting_key = :new WHERE setting_key = :old',
                ['new' => $new, 'old' => $old],
            );
            $this->addSql(
                'UPDATE core_users SET disabled_modules = REPLACE(disabled_modules::text, :old, :new)::jsonb WHERE disabled_modules::text LIKE :pattern',
                ['old' => '"'.$old.'"', 'new' => '"'.$new.'"', 'pattern' => '%"'.$old.'"%'],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->addSql(
                'UPDATE core_settings SET setting_key = :old WHERE setting_key = :new',
                ['new' => $new, 'old' => $old],
            );
            $this->addSql(
                'UPDATE core_users SET disabled_modules = REPLACE(disabled_modules::text, :new, :old)::jsonb WHERE disabled_modules::text LIKE :pattern',
                ['old' => '"'.$old.'"', 'new' => '"'.$new.'"', 'pattern' => '%"'.$new.'"%'],
            );
        }
    }
}
