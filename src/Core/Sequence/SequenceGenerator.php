<?php

declare(strict_types=1);

namespace Aurora\Core\Sequence;

use Doctrine\DBAL\Connection;

/**
 * Atomic sequential number generator backed by PostgreSQL sequences.
 *
 * Each logical series maps to one named sequence:
 *   - yearly (reset each year): seq_{prefix}_{year}  →  FAC-2026-0001
 *   - global  (never resets):   seq_{prefix}          →  ORD-000001
 *
 * Sequences are created on first use (CREATE SEQUENCE IF NOT EXISTS) so no
 * migration is needed when a new prefix appears. NEXTVAL is atomic —
 * concurrent requests can never produce the same number.
 */
final readonly class SequenceGenerator
{
    public function __construct(private Connection $connection) {}

    /**
     * Yearly sequence — resets to 1 each calendar year.
     * Format: {PREFIX}-{YEAR}-{NNNN}.
     */
    public function nextYearly(string $prefix, int $year, int $pad = 4): string
    {
        $seqName = $this->seqName($prefix, $year);
        $this->ensureSequence($seqName);
        $next = (int) $this->connection->executeQuery(sprintf("SELECT NEXTVAL('%s')", $seqName))->fetchOne();

        return sprintf('%s-%d-%0'.$pad.'d', $prefix, $year, $next);
    }

    /**
     * Global sequence — never resets.
     * Format: {PREFIX}-{NNNNNN}.
     */
    public function next(string $prefix, int $pad = 6): string
    {
        $seqName = $this->seqName($prefix);
        $this->ensureSequence($seqName);
        $next = (int) $this->connection->executeQuery(sprintf("SELECT NEXTVAL('%s')", $seqName))->fetchOne();

        return sprintf('%s-%0'.$pad.'d', $prefix, $next);
    }

    /**
     * Return the current value without consuming it (useful for display).
     * Returns 0 if the sequence has never been called.
     */
    public function current(string $prefix, ?int $year = null): int
    {
        $seqName = $this->seqName($prefix, $year);
        $exists = (bool) $this->connection->executeQuery(
            "SELECT 1 FROM pg_sequences WHERE schemaname = 'public' AND sequencename = ?",
            [$seqName],
        )->fetchOne();

        if (!$exists) {
            return 0;
        }

        return (int) $this->connection->executeQuery('SELECT last_value FROM '.$seqName)->fetchOne();
    }

    private function seqName(string $prefix, ?int $year = null): string
    {
        $slug = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $prefix) ?? $prefix);

        return null !== $year ? sprintf('seq_%s_%d', $slug, $year) : 'seq_'.$slug;
    }

    private function ensureSequence(string $seqName): void
    {
        $this->connection->executeStatement(sprintf('CREATE SEQUENCE IF NOT EXISTS %s START 1 INCREMENT 1 NO CYCLE', $seqName));
    }
}
