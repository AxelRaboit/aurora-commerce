<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Cleanup of the legacy `core.media.manage` privilege still lingering in
 * some users' `core_users.privileges` JSON column.
 *
 * Background:
 *  - Phase B (commit 2f615410) split the catch-all `core.media.manage`
 *    into 3 granular perms `core.media.{create,edit,delete}` but never
 *    shipped a data migration — the assumption was that fresh fixtures
 *    would replace the old value.
 *  - Jalon 5 (Version20260516180000) renamed `core.media.*` to `media.*`
 *    but its rename map only covered `core.media.{view,create,edit,delete}`,
 *    so any user who still had the legacy `core.media.manage` (i.e. whose
 *    fixtures were loaded before commit 2f615410) ends up with an
 *    orphan key that doesn't match any current NavPermission. The
 *    privileges modal shows them as raw keys.
 *
 * This migration replaces every `"core.media.manage"` occurrence in the
 * JSON arrays with the 3 granular successors, preserving the user's
 * effective access (write-all → create + edit + delete).
 */
final class Version20260516220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cleanup core_users.privileges: split legacy core.media.manage into media.{create,edit,delete}';
    }

    public function up(Schema $schema): void
    {
        // REPLACE on the text representation: replace one JSON array
        // element with three. Works because elements in a JSON array
        // are comma-separated, so substituting the literal token
        // `"core.media.manage"` with `"media.create","media.edit","media.delete"`
        // produces a valid array — independently of the element's
        // position (start/middle/end/only).
        $this->addSql(
            "UPDATE core_users
             SET privileges = REPLACE(
                 privileges::text,
                 '\"core.media.manage\"',
                 '\"media.create\",\"media.edit\",\"media.delete\"'
             )::json
             WHERE privileges::text LIKE '%\"core.media.manage\"%'",
        );
    }

    public function down(Schema $schema): void
    {
        // Best-effort reverse: collapse the 3 granular back into manage
        // when they appear together. This is lossy (if the user later
        // adjusted the 3 perms independently we'd lose that granularity),
        // but acceptable for a rollback path.
        $this->addSql(
            "UPDATE core_users
             SET privileges = REPLACE(
                 privileges::text,
                 '\"media.create\",\"media.edit\",\"media.delete\"',
                 '\"core.media.manage\"'
             )::json
             WHERE privileges::text LIKE '%\"media.create\",\"media.edit\",\"media.delete\"%'",
        );
    }
}
