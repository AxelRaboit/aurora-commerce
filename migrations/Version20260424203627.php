<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the full-text search plumbing on post_translations:
 *   - search_content : plain text aggregation of title + meta + blocks + custom fields
 *   - search_vector  : PostgreSQL generated tsvector column, GIN-indexed for fast matching
 */
final class Version20260424203627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add search_content / search_vector on post_translations for full-text search';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post_translations ADD search_content TEXT DEFAULT NULL');
        $this->addSql(<<<'SQL'
                ALTER TABLE post_translations
                ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (
                    setweight(to_tsvector('simple', coalesce(title, '')), 'A')
                    || setweight(to_tsvector('simple', coalesce(search_content, '')), 'B')
                ) STORED
            SQL);
        $this->addSql('CREATE INDEX idx_post_translations_search_vector ON post_translations USING GIN (search_vector)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_post_translations_search_vector');
        $this->addSql('ALTER TABLE post_translations DROP COLUMN IF EXISTS search_vector');
        $this->addSql('ALTER TABLE post_translations DROP search_content');
    }
}
