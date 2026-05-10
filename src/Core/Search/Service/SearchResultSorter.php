<?php

declare(strict_types=1);

namespace Aurora\Core\Search\Service;

use Aurora\Module\Editorial\Post\Entity\PostInterface;

/**
 * Sorts a flat list of Post entities according to a relevance-ordered ID list.
 *
 * Full-text search engines return IDs ranked by relevance; Doctrine fetches
 * them by primary key (unordered). This service re-applies the original
 * ranking after the hydration step.
 */
final readonly class SearchResultSorter
{
    /**
     * @param list<PostInterface> $posts      hydrated post entities to sort
     * @param list<int>           $orderedIds IDs in relevance order (best match first)
     *
     * @return list<PostInterface>
     */
    public function sortByRelevance(array $posts, array $orderedIds): array
    {
        $orderById = array_flip($orderedIds);
        usort($posts, static fn (PostInterface $postA, PostInterface $postB): int => ($orderById[$postA->getId()] ?? PHP_INT_MAX) <=> ($orderById[$postB->getId()] ?? PHP_INT_MAX));

        return $posts;
    }
}
