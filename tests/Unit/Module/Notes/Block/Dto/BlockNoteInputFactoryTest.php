<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Block\Dto;

use Aurora\Module\Notes\Block\Dto\BlockNoteInputFactory;
use PHPUnit\Framework\TestCase;

final class BlockNoteInputFactoryTest extends TestCase
{
    private BlockNoteInputFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new BlockNoteInputFactory();
    }

    public function testEmptyArrayProducesAllNullsAndEmptyTags(): void
    {
        $input = $this->factory->fromArray([]);

        self::assertNull($input->getParentId());
        self::assertNull($input->getTitle());
        self::assertSame([], $input->getTags());
        self::assertNull($input->getPosition());
        // `blocks` absent → null means "do not touch the collection" (metadata-only update).
        self::assertNull($input->getBlocks());
    }

    public function testFullPayloadIsHydrated(): void
    {
        $input = $this->factory->fromArray([
            'parentId' => '42',
            'title' => '  Block note  ',
            'tags' => ['  foo ', 'bar', ''],
            'position' => '3',
            'blocks' => [
                ['id' => 'b1', 'type' => 'paragraph', 'data' => ['text' => 'Hello']],
            ],
        ]);

        self::assertSame(42, $input->getParentId());
        self::assertSame('Block note', $input->getTitle());
        self::assertSame(['foo', 'bar'], $input->getTags());
        self::assertSame(3, $input->getPosition());

        $blocks = $input->getBlocks();
        self::assertCount(1, $blocks);
        self::assertSame('b1', $blocks[0]->id);
        self::assertSame('paragraph', $blocks[0]->type);
        self::assertSame(['text' => 'Hello'], $blocks[0]->data);
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

    public function testWhitespaceTitleBecomesNull(): void
    {
        $input = $this->factory->fromArray(['title' => '   ']);

        self::assertNull($input->getTitle());
    }

    public function testBlocksNullKeepsNullSemanticVsEmptyArray(): void
    {
        // Null means "do not touch blocks" (metadata-only update path).
        self::assertNull($this->factory->fromArray(['blocks' => null])->getBlocks());

        // Empty array means "clear all blocks" — distinct intent, must hydrate.
        self::assertSame([], $this->factory->fromArray(['blocks' => []])->getBlocks());
    }

    public function testBlocksNonArrayFallsBackToEmpty(): void
    {
        // A non-array, non-null `blocks` is malformed input → treat as empty
        // (clear) rather than null (untouched) so the user doesn't get
        // their existing blocks silently kept on a broken payload.
        self::assertSame([], $this->factory->fromArray(['blocks' => 'garbage'])->getBlocks());
    }

    public function testBlocksDropsEntriesThatAreNotArrays(): void
    {
        $input = $this->factory->fromArray([
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'kept']],
                'invalid',
                42,
                ['type' => 'heading', 'data' => ['text' => 'also kept']],
            ],
        ]);

        $blocks = $input->getBlocks();
        self::assertCount(2, $blocks);
        self::assertSame('paragraph', $blocks[0]->type);
        self::assertSame('heading', $blocks[1]->type);
    }

    public function testBlockMissingTypeBecomesEmptyString(): void
    {
        // Validation on the DTO will then reject it via Assert\NotBlank;
        // the factory's job is just to bind input verbatim.
        $input = $this->factory->fromArray([
            'blocks' => [['data' => ['text' => 'no type']]],
        ]);

        $blocks = $input->getBlocks();
        self::assertSame('', $blocks[0]->type);
        self::assertSame(['text' => 'no type'], $blocks[0]->data);
    }

    public function testBlockMissingDataBecomesEmptyArray(): void
    {
        $input = $this->factory->fromArray([
            'blocks' => [['type' => 'delimiter']],
        ]);

        $blocks = $input->getBlocks();
        self::assertSame([], $blocks[0]->data);
    }

    public function testBlockIdIsKeptOnlyIfString(): void
    {
        // Editor.js generates string ids; non-string ids (numeric, null) are
        // not part of the contract — they should not crash.
        $input = $this->factory->fromArray([
            'blocks' => [
                ['id' => 'abc', 'type' => 'paragraph', 'data' => []],
                ['id' => 123, 'type' => 'paragraph', 'data' => []],
                ['type' => 'paragraph', 'data' => []],
            ],
        ]);

        $blocks = $input->getBlocks();
        self::assertSame('abc', $blocks[0]->id);
        self::assertNull($blocks[1]->id);
        self::assertNull($blocks[2]->id);
    }
}
