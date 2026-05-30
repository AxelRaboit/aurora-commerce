<?php

declare(strict_types=1);

namespace Aurora\Core\Search;

/**
 * Reorders a list of hydrated entities to match a relevance-ranked id list.
 *
 * Full-text engines return ids ranked by relevance; Doctrine fetches them by
 * primary key (unordered). This re-applies the original ranking. Generic (no
 * domain type) so it lives in core and any module's search provider can use it.
 */
final readonly class RelevanceSorter
{
    /**
     * @template T of object
     *
     * @param list<T>          $items      hydrated entities to sort
     * @param list<int>        $orderedIds ids in relevance order (best first)
     * @param callable(T): int $idOf       extracts an item's id
     *
     * @return list<T>
     */
    public function sort(array $items, array $orderedIds, callable $idOf): array
    {
        $orderById = array_flip($orderedIds);
        usort(
            $items,
            static fn (object $itemA, object $itemB): int => ($orderById[$idOf($itemA)] ?? PHP_INT_MAX) <=> ($orderById[$idOf($itemB)] ?? PHP_INT_MAX),
        );

        return $items;
    }
}
