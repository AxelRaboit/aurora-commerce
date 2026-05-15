<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Gallery\Dto;

use Aurora\Module\Photo\Gallery\Dto\GalleryItemBulkDeleteInput;
use PHPUnit\Framework\TestCase;

final class GalleryItemBulkDeleteInputTest extends TestCase
{
    public function testDefaultItemIdsIsEmpty(): void
    {
        self::assertSame([], (new GalleryItemBulkDeleteInput())->itemIds);
    }

    public function testFromArrayFiltersPositiveInts(): void
    {
        $input = GalleryItemBulkDeleteInput::fromArray(['itemIds' => [1, '2', 0, -1, '3']]);

        self::assertSame([1, 2, 3], $input->itemIds);
    }

    public function testFromArrayWithMissingKey(): void
    {
        $input = GalleryItemBulkDeleteInput::fromArray([]);

        self::assertSame([], $input->itemIds);
    }
}
