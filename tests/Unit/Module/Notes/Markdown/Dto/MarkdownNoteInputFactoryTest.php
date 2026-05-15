<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Markdown\Dto;

use Aurora\Module\Notes\Markdown\Dto\MarkdownNoteInputFactory;
use PHPUnit\Framework\TestCase;

final class MarkdownNoteInputFactoryTest extends TestCase
{
    private MarkdownNoteInputFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MarkdownNoteInputFactory();
    }

    public function testEmptyArrayProducesAllNulls(): void
    {
        $input = $this->factory->fromArray([]);

        self::assertNull($input->getParentId());
        self::assertNull($input->getTitle());
        self::assertNull($input->getContent());
        self::assertSame([], $input->getTags());
        self::assertNull($input->getPosition());
    }

    public function testFullPayloadIsHydrated(): void
    {
        $input = $this->factory->fromArray([
            'parentId' => '42',
            'title' => '  My note  ',
            'content' => "# Heading\n\nBody",
            'tags' => ['  foo ', 'bar', ''],
            'position' => '3',
        ]);

        self::assertSame(42, $input->getParentId());
        self::assertSame('My note', $input->getTitle());
        self::assertSame("# Heading\n\nBody", $input->getContent());
        self::assertSame(['foo', 'bar'], $input->getTags());
        self::assertSame(3, $input->getPosition());
    }

    public function testTagsAreTrimmedDedupedAndStringFiltered(): void
    {
        $input = $this->factory->fromArray([
            'tags' => [' alpha', 'alpha', 'beta ', 42, '', 'gamma'],
        ]);

        self::assertSame(['alpha', 'beta', 'gamma'], $input->getTags());
    }

    public function testInvalidTagsTypeFallsBackToEmpty(): void
    {
        $input = $this->factory->fromArray(['tags' => 'not-an-array']);

        self::assertSame([], $input->getTags());
    }

    public function testEmptyContentStringBecomesNull(): void
    {
        $input = $this->factory->fromArray(['content' => '']);

        self::assertNull($input->getContent());
    }

    public function testWhitespaceTitleBecomesNull(): void
    {
        $input = $this->factory->fromArray(['title' => '   ']);

        self::assertNull($input->getTitle());
    }
}
