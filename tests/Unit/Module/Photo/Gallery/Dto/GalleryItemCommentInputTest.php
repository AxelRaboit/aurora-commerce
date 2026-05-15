<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Gallery\Dto;

use Aurora\Module\Photo\Gallery\Dto\GalleryItemCommentInput;
use PHPUnit\Framework\TestCase;

final class GalleryItemCommentInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new GalleryItemCommentInput();

        self::assertSame('', $input->content);
        self::assertNull($input->visitorName);
        self::assertNull($input->visitorEmail);
    }

    public function testFromArrayTrimsAndLowercasesEmail(): void
    {
        $input = GalleryItemCommentInput::fromArray([
            'content' => '  Great shot  ',
            'name' => '  Jane  ',
            'email' => '  Jane@Example.com  ',
        ]);

        self::assertSame('Great shot', $input->content);
        self::assertSame('Jane', $input->visitorName);
        self::assertSame('jane@example.com', $input->visitorEmail);
    }

    public function testFromArrayWithMissingOptionals(): void
    {
        $input = GalleryItemCommentInput::fromArray(['content' => 'X']);

        self::assertNull($input->visitorName);
        self::assertNull($input->visitorEmail);
    }

    public function testMaxLengthConstant(): void
    {
        self::assertSame(2000, GalleryItemCommentInput::MAX_LENGTH);
    }
}
