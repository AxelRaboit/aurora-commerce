<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fold `core_blocks` into a JSON column on `core_block_notes`.
 *
 * The original schema modelled blocks as a separate table with a OneToMany
 * relation. After running into reorder bugs (Doctrine Collection order
 * not refreshed after in-memory setPosition) and the realisation that
 * blocks have no independent lifecycle (no per-block queries, no cross-
 * note sharing, no separate audit), the storage was collapsed to a JSON
 * `list<{type, data}>` on the note itself — matching how `core_post`
 * stores its blocks. Identity becomes the array index; ordering is the
 * array order.
 *
 * Existing rows are migrated via json_agg ORDER BY position, then the
 * table + sequence are dropped.
 */
final class Version20260517091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fold core_blocks into a JSON column on core_block_notes; drop the now-empty block table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE core_block_notes ADD COLUMN blocks JSON DEFAULT '[]' NOT NULL");

        // Backfill: aggregate blocks per note as a JSON list of {type, data},
        // ordered by the legacy position. data_json is TEXT so cast to JSON
        // before nesting it into the resulting object.
        $this->addSql(<<<'SQL'
            UPDATE core_block_notes n
            SET blocks = COALESCE(sub.payload, '[]'::json)
            FROM (
                SELECT note_id,
                       json_agg(json_build_object('type', type, 'data', COALESCE(data_json::json, '{}'::json)) ORDER BY position, id) AS payload
                FROM core_blocks
                GROUP BY note_id
            ) AS sub
            WHERE n.id = sub.note_id
            SQL);

        $this->addSql('ALTER TABLE core_blocks DROP CONSTRAINT FK_6EB1F62126ED0855');
        $this->addSql('DROP TABLE core_blocks');
        $this->addSql('DROP SEQUENCE seq_core_block_id CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_block_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_blocks (type VARCHAR(32) NOT NULL, data_json TEXT DEFAULT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, note_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_blocks_note ON core_blocks (note_id)');
        $this->addSql('CREATE INDEX idx_blocks_type ON core_blocks (type)');
        $this->addSql('ALTER TABLE core_blocks ADD CONSTRAINT FK_6EB1F62126ED0855 FOREIGN KEY (note_id) REFERENCES core_block_notes (id) ON DELETE CASCADE NOT DEFERRABLE');

        // Re-explode the JSON list into rows. WITH ORDINALITY preserves
        // the array order so the recreated position column matches what
        // the user last saw.
        $this->addSql(<<<'SQL'
            INSERT INTO core_blocks (id, note_id, type, data_json, position, created_at, updated_at)
            SELECT nextval('seq_core_block_id'),
                   n.id,
                   elem->>'type',
                   (elem->'data')::text,
                   ord - 1,
                   n.created_at,
                   n.updated_at
            FROM core_block_notes n,
                 LATERAL json_array_elements(n.blocks) WITH ORDINALITY AS t(elem, ord)
            WHERE json_array_length(n.blocks) > 0
            SQL);

        $this->addSql('ALTER TABLE core_block_notes DROP COLUMN blocks');
    }
}
