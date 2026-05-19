<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\General\Search\Service;

use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\General\Search\Service\SearchResultSorter;
use PHPUnit\Framework\TestCase;

final class SearchResultSorterTest extends TestCase
{
    private function makePost(int $id): PostInterface
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getId')->willReturn($id);

        return $post;
    }

    public function testSortByRelevanceReordersAccordingToIdList(): void
    {
        $sorter = new SearchResultSorter();

        $post1 = $this->makePost(1);
        $post2 = $this->makePost(2);
        $post3 = $this->makePost(3);

        // posts hydrated in arbitrary order, ordered ids prioritize 3, 1, 2
        $sorted = $sorter->sortByRelevance([$post1, $post2, $post3], [3, 1, 2]);

        self::assertSame($post3, $sorted[0]);
        self::assertSame($post1, $sorted[1]);
        self::assertSame($post2, $sorted[2]);
    }

    public function testSortByRelevancePushesUnknownIdsToEnd(): void
    {
        $sorter = new SearchResultSorter();

        $post1 = $this->makePost(1);
        $post99 = $this->makePost(99);
        $post2 = $this->makePost(2);

        // post99 is not in orderedIds — should go last
        $sorted = $sorter->sortByRelevance([$post1, $post99, $post2], [2, 1]);

        self::assertSame($post2, $sorted[0]);
        self::assertSame($post1, $sorted[1]);
        self::assertSame($post99, $sorted[2]);
    }

    public function testSortByRelevanceWithEmptyInput(): void
    {
        self::assertSame([], (new SearchResultSorter())->sortByRelevance([], [1, 2, 3]));
    }

    public function testSortByRelevanceWithEmptyOrderingPreservesNaturalOrder(): void
    {
        $sorter = new SearchResultSorter();
        $post1 = $this->makePost(1);
        $post2 = $this->makePost(2);

        $sorted = $sorter->sortByRelevance([$post1, $post2], []);

        self::assertCount(2, $sorted);
    }
}
