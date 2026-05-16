<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Jalon 5 — align privilege names on their owner module id.
 *
 * After Jalon 4 (CoreModule split into 4) + Jalon 4.5 (Media extracted),
 * the `core.*` prefix on permission names became misleading: the
 * permissions were declared by PlatformModule / MediaModule /
 * ConfigurationModule, not by a now-defunct CoreModule. The business
 * module pattern is `<module_id>.<entity>.<action>` (editorial.posts.view,
 * crm.contacts.create); the Core sub-modules now follow the same shape.
 *
 * Rename map (longest first to avoid prefix collisions during REPLACE):
 *
 *   core.users.modules.manage  → platform.users.modules.manage
 *   core.users.manage          → platform.users.manage
 *   core.agencies.manage       → platform.agencies.manage
 *   core.services.manage       → platform.services.manage
 *   core.search.view           → platform.search.view
 *   core.media.folders.create  → media.folders.create
 *   core.media.folders.edit    → media.folders.edit
 *   core.media.folders.delete  → media.folders.delete
 *   core.media.view            → media.view
 *   core.media.create          → media.create
 *   core.media.edit            → media.edit
 *   core.media.delete          → media.delete
 *   core.settings.manage       → configuration.settings.manage
 *   core.themes.manage         → configuration.themes.manage
 *
 * Migrates the JSONB `core_users.privileges` column (the list of perms
 * granted to each user) by string-replacing the legacy keys with their
 * new names. The textual REPLACE is safe here because the rename map
 * is ordered by length descending — no value is a prefix of an
 * already-renamed value once `users.modules.manage` is consumed.
 */
final class Version20260516180000 extends AbstractMigration
{
    /**
     * Ordered: more specific (longer) keys first so a REPLACE on the
     * shorter version doesn't clobber the prefix of the longer one.
     */
    private const array RENAMES = [
        'core.users.modules.manage' => 'platform.users.modules.manage',
        'core.users.manage' => 'platform.users.manage',
        'core.agencies.manage' => 'platform.agencies.manage',
        'core.services.manage' => 'platform.services.manage',
        'core.search.view' => 'platform.search.view',
        'core.media.folders.create' => 'media.folders.create',
        'core.media.folders.edit' => 'media.folders.edit',
        'core.media.folders.delete' => 'media.folders.delete',
        'core.media.view' => 'media.view',
        'core.media.create' => 'media.create',
        'core.media.edit' => 'media.edit',
        'core.media.delete' => 'media.delete',
        'core.settings.manage' => 'configuration.settings.manage',
        'core.themes.manage' => 'configuration.themes.manage',
    ];

    public function getDescription(): string
    {
        return 'Jalon 5 — align core.* privilege names on owner module id (platform.*, media.*, configuration.*)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->addSql(
                'UPDATE core_users SET privileges = REPLACE(privileges::text, :old, :new)::jsonb WHERE privileges::text LIKE :pattern',
                ['old' => '"'.$old.'"', 'new' => '"'.$new.'"', 'pattern' => '%"'.$old.'"%'],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Reverse rename. Order doesn't matter symmetrically since the new
        // names share no prefix relationships (platform.*, media.*,
        // configuration.* are disjoint).
        foreach (self::RENAMES as $old => $new) {
            $this->addSql(
                'UPDATE core_users SET privileges = REPLACE(privileges::text, :new, :old)::jsonb WHERE privileges::text LIKE :pattern',
                ['old' => '"'.$old.'"', 'new' => '"'.$new.'"', 'pattern' => '%"'.$new.'"%'],
            );
        }
    }
}
