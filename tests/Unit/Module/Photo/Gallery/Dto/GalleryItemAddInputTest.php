<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Gallery\Dto;

use Aurora\Module\Photo\Gallery\Dto\GalleryItemAddInput;
use PHPUnit\Framework\TestCase;

final class GalleryItemAddInputTest extends TestCase
{
    public function testDefaultMediaIdsIsEmpty(): void
    {
        self::assertSame([], (new GalleryItemAddInput())->mediaIds);
    }

    public function testFromArrayFiltersPositiveInts(): void
    {
        $input = GalleryItemAddInput::fromArray(['mediaIds' => [1, '2', 0, -1, '3']]);

        self::assertSame([1, 2, 3], $input->mediaIds);
    }

    public function testFromArrayWithMissingKey(): void
    {
        $input = GalleryItemAddInput::fromArray([]);

        self::assertSame([], $input->mediaIds);
    }

    public function testFromArrayWithNonArrayValue(): void
    {
        $input = GalleryItemAddInput::fromArray(['mediaIds' => 'invalid']);

        self::assertSame([], $input->mediaIds);
    }
}
