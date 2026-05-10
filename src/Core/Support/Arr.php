<?php

declare(strict_types=1);

namespace Aurora\Core\Support;

final class Arr
{
    private function __construct() {}

    /**
     * Converts a raw mixed value to a list of positive integers.
     * Non-array input, non-numeric values, and IDs ≤ 0 are discarded.
     *
     * Typical usage: parsing `orderedIds` / `ids` payloads from the frontend.
     *
     * @return list<int>
     */
    public static function positiveInts(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(static fn (mixed $id): int => (int) $id, $raw),
                static fn (int $id): bool => $id > 0,
            )
        );
    }
}
