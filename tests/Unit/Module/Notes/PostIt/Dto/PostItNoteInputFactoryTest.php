<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\PostIt\Dto;

use Aurora\Module\Notes\PostIt\Dto\PostItNoteInputFactory;
use PHPUnit\Framework\TestCase;

final class PostItNoteInputFactoryTest extends TestCase
{
    private PostItNoteInputFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new PostItNoteInputFactory();
    }

    public function testEmptyArrayProducesAllNulls(): void
    {
        $input = $this->factory->fromArray([]);

        self::assertNull($input->getTitle());
        self::assertNull($input->getContent());
        self::assertNull($input->getColor());
        self::assertNull($input->getPositionX());
        self::assertNull($input->getPositionY());
    }

    public function testFullPayloadIsHydrated(): void
    {
        $input = $this->factory->fromArray([
            'title' => '  Shopping list  ',
            'content' => "milk\neggs",
            'color' => '#FFEB3B',
            'positionX' => '120',
            'positionY' => '80',
        ]);

        self::assertSame('Shopping list', $input->getTitle());
        self::assertSame("milk\neggs", $input->getContent());
        self::assertSame('#FFEB3B', $input->getColor());
        self::assertSame(120, $input->getPositionX());
        self::assertSame(80, $input->getPositionY());
    }

    public function testWhitespaceTitleBecomesNull(): void
    {
        $input = $this->factory->fromArray(['title' => '   ']);

        self::assertNull($input->getTitle());
    }

    public function testEmptyContentStringBecomesNull(): void
    {
        $input = $this->factory->fromArray(['content' => '']);

        self::assertNull($input->getContent());
    }

    public function testNonStringContentBecomesNull(): void
    {
        // Non-string values land as null — defensive parsing avoids
        // accidental coercion of arrays/numbers into text payloads.
        $input = $this->factory->fromArray(['content' => 42]);

        self::assertNull($input->getContent());
    }

    public function testColorIsTrimmed(): void
    {
        $input = $this->factory->fromArray(['color' => '  #A5D6A7  ']);

        self::assertSame('#A5D6A7', $input->getColor());
    }

    public function testPositionsAreCastToInt(): void
    {
        $input = $this->factory->fromArray([
            'positionX' => '42.7',
            'positionY' => '-15',
        ]);

        self::assertSame(42, $input->getPositionX());
        self::assertSame(-15, $input->getPositionY());
    }

    public function testMissingPositionsAreNullNotZero(): void
    {
        // Manager treats null as "no change" — distinguishing missing from
        // an explicit 0 is crucial for partial-update semantics.
        $input = $this->factory->fromArray(['title' => 'X']);

        self::assertNull($input->getPositionX());
        self::assertNull($input->getPositionY());
    }
}
