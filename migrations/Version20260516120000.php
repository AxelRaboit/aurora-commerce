<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Jalon 4 — split CoreModule into 4 sibling modules
 * (General/Platform/Configuration/Dev). Persisted settings rename to
 * follow:
 *
 *   modules_platform_settings → modules_configuration_settings
 *   modules_platform_themes   → modules_configuration_themes
 *
 * Two side-effects also handled here:
 *
 * 1. If a site had explicitly disabled the Platform backend
 *    (`modules_platform_backend = '0'`), mirror that intent on the new
 *    `modules_configuration_backend` toggle so configuration items don't
 *    silently re-enable post-migration. Settings/Themes used to cascade
 *    off Platform; after the split they cascade off Configuration.
 *
 * 2. The General section's NavSection.id moved from 'core' to 'general'
 *    so it aligns with `getModuleId()`. The admin-wide aliases stored in
 *    `nav_section_aliases` are keyed by NavSection.id — rename the key
 *    in-place to preserve any custom label set by an admin.
 *
 * Also updates each user's `disabled_modules` JSON mask so per-user
 * overrides on the renamed keys keep working.
 */
final class Version20260516120000 extends AbstractMigration
{
    private const array SETTING_RENAMES = [
        'modules_platform_settings' => 'modules_configuration_settings',
        'modules_platform_themes' => 'modules_configuration_themes',
    ];

    public function getDescription(): string
    {
        return 'Jalon 4 — rename modules_platform_{settings,themes} to modules_configuration_* and migrate nav_section_aliases.core → general';
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

        // Preserve "Platform off" intent on the new Configuration backend toggle:
        // when an admin had explicitly disabled Platform, the cascade used to
        // also disable Settings/Themes; mirror the off-state explicitly now.
        $this->addSql(
            "INSERT INTO core_settings (setting_key, value, type, \"group\", description)
             SELECT 'modules_configuration_backend', '0', 'bool', 'modules', NULL
             FROM core_settings WHERE setting_key = 'modules_platform_backend' AND value = '0'
             ON CONFLICT (setting_key) DO NOTHING",
        );

        // NavSection.id 'core' (Dashboard) → 'general'. Preserve any admin alias.
        $this->addSql(
            "UPDATE core_settings
             SET value = REPLACE(value, '\"core\":', '\"general\":')
             WHERE setting_key = 'nav_section_aliases' AND value LIKE '%\"core\":%'",
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE core_settings
             SET value = REPLACE(value, '\"general\":', '\"core\":')
             WHERE setting_key = 'nav_section_aliases' AND value LIKE '%\"general\":%'",
        );

        $this->addSql("DELETE FROM core_settings WHERE setting_key = 'modules_configuration_backend'");

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
