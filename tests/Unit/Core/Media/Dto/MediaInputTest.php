<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Media\Dto;

use Aurora\Core\Media\Dto\MediaInput;
use PHPUnit\Framework\TestCase;

final class MediaInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new MediaInput();

        self::assertNull($input->getAlt());
        self::assertNull($input->getCaption());
        self::assertNull($input->getFocalX());
        self::assertNull($input->getFocalY());
        self::assertNull($input->getFolderId());
    }

    public function testConstructorValues(): void
    {
        $input = new MediaInput(
            alt: 'Photo description',
            caption: 'Caption',
            focalX: 0.5,
            focalY: 0.25,
            folderId: 42,
        );

        self::assertSame('Photo description', $input->getAlt());
        self::assertSame('Caption', $input->getCaption());
        self::assertSame(0.5, $input->getFocalX());
        self::assertSame(0.25, $input->getFocalY());
        self::assertSame(42, $input->getFolderId());
    }
}
