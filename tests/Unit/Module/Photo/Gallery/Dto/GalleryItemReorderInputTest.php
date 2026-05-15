<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Gallery\Dto;

use Aurora\Module\Photo\Gallery\Dto\GalleryItemReorderInput;
use PHPUnit\Framework\TestCase;

final class GalleryItemReorderInputTest extends TestCase
{
    public function testDefaultItemIdsIsEmpty(): void
    {
        self::assertSame([], (new GalleryItemReorderInput())->itemIds);
    }

    public function testFromArrayFiltersPositiveInts(): void
    {
        $input = GalleryItemReorderInput::fromArray(['itemIds' => [3, '1', 0, '2']]);

        self::assertSame([3, 1, 2], $input->itemIds);
    }

    public function testFromArrayWithMissingKey(): void
    {
        $input = GalleryItemReorderInput::fromArray([]);

        self::assertSame([], $input->itemIds);
    }
}
