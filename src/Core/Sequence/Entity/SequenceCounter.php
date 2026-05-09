<?php

declare(strict_types=1);

namespace Aurora\Core\Sequence\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores the current counter value for each business reference series.
 *
 * The primary key is (prefix, year):
 *   - year = 0   → global sequence  (LOG, ORD, …)
 *   - year = YYYY → yearly sequence  (FAC-2026-…)
 *
 * SequenceGenerator uses raw DBAL (INSERT … ON CONFLICT DO UPDATE RETURNING)
 * for atomic increments. This entity exists solely for Doctrine schema management.
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_sequence_counters')]
class SequenceCounter
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 30)]
        public readonly string $prefix,
        #[ORM\Id]
        #[ORM\Column(options: ['default' => 0])]
        public readonly int $year = 0,
        #[ORM\Column(options: ['default' => 0])]
        public int $lastValue = 0
    ) {}
}
