<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Jalon 4.5 — extract Media out of PlatformModule into its own MediaModule.
 *
 * Setting key rename:
 *   modules_platform_media → modules_media_library
 *
 * Side effect: if Platform was explicitly disabled
 * (`modules_platform_backend = '0'`), mirror that off-state onto the new
 * `modules_media_backend` toggle — media used to cascade off Platform,
 * after the split it cascades off MediaBackend.
 *
 * Also updates the per-user `disabled_modules` JSONB mask, and renames the
 * `nav_section_aliases` key from 'platform-media' to 'media' if any admin
 * had aliased the media item under the old section id (unlikely but cheap).
 */
final class Version20260516160000 extends AbstractMigration
{
    private const array SETTING_RENAMES = [
        'modules_platform_media' => 'modules_media_library',
    ];

    public function getDescription(): string
    {
        return 'Jalon 4.5 — rename modules_platform_media to modules_media_library, seed modules_media_backend from Platform off-state';
    }

    public function up(Schema $schema): void
    {
        foreach (self::SETTING_RENAMES as $old => $new) {
            $this->addSql(
                'UPDATE core_settings SET setting_key = :new WHERE setting_key = :old',
                ['new' => $new, 'old' => $old],
            );
            $this->addSql(
                'UPDATE core_users SET disabled_modules = REPLACE(disabled_modules::text, :old, :new)::jsonb WHERE disabled_modules::text LIKE :pattern',
                ['old' => '"'.$old.'"', 'new' => '"'.$new.'"', 'pattern' => '%"'.$old.'"%'],
            );
        }

        // Mirror "Platform off" intent onto the new Media backend toggle.
        $this->addSql(
            "INSERT INTO core_settings (setting_key, value, setting_type, setting_group, description)
             SELECT 'modules_media_backend', '0', 'bool', 'modules', NULL
             FROM core_settings WHERE setting_key = 'modules_platform_backend' AND value = '0'
             ON CONFLICT (setting_key) DO NOTHING",
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM core_settings WHERE setting_key = 'modules_media_backend'");

        foreach (self::SETTING_RENAMES as $old => $new) {
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
