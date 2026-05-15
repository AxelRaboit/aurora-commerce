<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Gallery\Dto;

use Aurora\Module\Photo\Gallery\Dto\GalleryItemCaptionInput;
use PHPUnit\Framework\TestCase;

final class GalleryItemCaptionInputTest extends TestCase
{
    public function testDefaultCaptionIsNull(): void
    {
        self::assertNull((new GalleryItemCaptionInput())->caption);
    }

    public function testConstructorCaption(): void
    {
        self::assertSame('A photo', (new GalleryItemCaptionInput('A photo'))->caption);
    }

    public function testFromArrayTrimsCaption(): void
    {
        $input = GalleryItemCaptionInput::fromArray(['caption' => '  Hello  ']);

        self::assertSame('Hello', $input->caption);
    }

    public function testFromArrayWithMissingCaption(): void
    {
        $input = GalleryItemCaptionInput::fromArray([]);

        self::assertNull($input->caption);
    }

    public function testFromArrayWithWhitespaceOnly(): void
    {
        $input = GalleryItemCaptionInput::fromArray(['caption' => '   ']);

        self::assertNull($input->caption);
    }

    public function testMaxLengthConstant(): void
    {
        self::assertSame(500, GalleryItemCaptionInput::MAX_LENGTH);
    }
}
