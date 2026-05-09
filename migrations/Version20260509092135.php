<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509092135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace app_seq_* PostgreSQL sequences with app_sequence_counters table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_sequence_counters (prefix VARCHAR(30) NOT NULL, year INT DEFAULT 0 NOT NULL, last_value INT DEFAULT 0 NOT NULL, PRIMARY KEY (prefix, year))');

        // Migrate existing app_seq_* sequences to the counter table.
        // last_value in the table = last number already generated (next call returns last_value + 1).
        // We derive the max from the reference columns to be safe.
        $this->addSql("
            INSERT INTO app_sequence_counters (prefix, year, last_value)
            SELECT 'LOG', 0, COALESCE(
                MAX(CAST(REGEXP_REPLACE(reference, '^.*-([0-9]+)$', '\\1') AS INTEGER)), 0
            )
            FROM core_audit_logs
            WHERE reference LIKE 'LOG-%'
        ");

        // Drop the now-obsolete PostgreSQL sequences.
        $this->addSql('DROP SEQUENCE IF EXISTS app_seq_log');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_sequence_counters');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS app_seq_log START 1 INCREMENT 1 NO CYCLE');
    }
}
