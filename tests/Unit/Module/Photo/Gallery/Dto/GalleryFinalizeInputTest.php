<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Gallery\Dto;

use Aurora\Module\Photo\Gallery\Dto\GalleryFinalizeInput;
use PHPUnit\Framework\TestCase;

final class GalleryFinalizeInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new GalleryFinalizeInput();

        self::assertSame('', $input->name);
        self::assertSame('', $input->email);
    }

    public function testFromArrayTrimsAndLowercasesEmail(): void
    {
        $input = GalleryFinalizeInput::fromArray([
            'name' => '  Jane  ',
            'email' => '  Jane@Example.com  ',
        ]);

        self::assertSame('Jane', $input->name);
        self::assertSame('jane@example.com', $input->email);
    }

    public function testFromArrayWithMissingFields(): void
    {
        $input = GalleryFinalizeInput::fromArray([]);

        self::assertSame('', $input->name);
        self::assertSame('', $input->email);
    }
}
