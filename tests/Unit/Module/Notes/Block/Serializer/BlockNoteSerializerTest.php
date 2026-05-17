<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Block\Serializer;

use Aurora\Module\Notes\Block\Entity\AbstractBlockNote;
use Aurora\Module\Notes\Block\Entity\BlockNote;
use Aurora\Module\Notes\Block\Serializer\BlockNoteSerializer;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class BlockNoteSerializerTest extends TestCase
{
    private BlockNoteSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new BlockNoteSerializer();
    }

    public function testListItemOmitsBlocks(): void
    {
        $note = $this->makeNote(id: 7, title: 'Hello', blocks: [
            ['type' => 'paragraph', 'data' => ['text' => 'should be hidden']],
        ]);

        $payload = $this->serializer->serializeListItem($note);

        self::assertArrayNotHasKey('blocks', $payload);
        self::assertSame(7, $payload['id']);
        self::assertSame('Hello', $payload['title']);
    }

    public function testDetailIncludesBlocks(): void
    {
        $note = $this->makeNote(id: 7, blocks: [
            ['type' => 'paragraph', 'data' => ['text' => 'body']],
        ]);

        $payload = $this->serializer->serializeDetail($note);

        self::assertCount(1, $payload['blocks']);
        self::assertSame('paragraph', $payload['blocks'][0]['type']);
        self::assertSame(['text' => 'body'], $payload['blocks'][0]['data']);
    }

    public function testSerializeBlockKeepsEditorJsIdWhenPresent(): void
    {
        $block = $this->serializer->serializeBlock([
            'id' => 'editor-js-uuid',
            'type' => 'heading',
            'data' => ['text' => 'Title', 'level' => 2],
        ]);

        self::assertSame('editor-js-uuid', $block['id']);
        self::assertSame('heading', $block['type']);
        self::assertSame(['text' => 'Title', 'level' => 2], $block['data']);
    }

    public function testSerializeBlockOmitsIdWhenAbsent(): void
    {
        $block = $this->serializer->serializeBlock([
            'type' => 'delimiter',
            'data' => [],
        ]);

        self::assertArrayNotHasKey('id', $block);
        self::assertSame('delimiter', $block['type']);
    }

    public function testSerializesTagsParentAndPosition(): void
    {
        $parent = $this->makeNote(id: 3);
        $child = $this->makeNote(id: 12, title: 'child', tags: ['a', 'b'], position: 4);
        $child->setParent($parent);

        $payload = $this->serializer->serializeListItem($child);

        self::assertSame(3, $payload['parentId']);
        self::assertSame(['a', 'b'], $payload['tags']);
        self::assertSame(4, $payload['position']);
    }

    public function testTimestampsAreSerializedAsAtom(): void
    {
        $note = $this->makeNote();

        $payload = $this->serializer->serializeListItem($note);

        self::assertSame((new DateTimeImmutable('2026-01-15T10:30:00+00:00'))->format(DateTimeInterface::ATOM), $payload['createdAt']);
        self::assertSame((new DateTimeImmutable('2026-01-15T11:00:00+00:00'))->format(DateTimeInterface::ATOM), $payload['updatedAt']);
    }

    public function testSerializeTagCountsFlattensHistogramToList(): void
    {
        $payload = $this->serializer->serializeTagCounts(['draft' => 5, 'done' => 2]);

        self::assertSame([
            ['tag' => 'draft', 'count' => 5],
            ['tag' => 'done', 'count' => 2],
        ], $payload);
    }

    /**
     * @param list<string>                                                       $tags
     * @param list<array{id?: string, type: string, data: array<string, mixed>}> $blocks
     */
    private function makeNote(
        ?int $id = 1,
        ?string $title = null,
        array $tags = [],
        int $position = 0,
        array $blocks = [],
    ): BlockNote {
        $note = new BlockNote();

        if (null !== $id) {
            $r = new ReflectionProperty(BlockNote::class, 'id');
            $r->setValue($note, $id);
        }

        $note->setTitle($title);
        $note->setTags($tags);
        $note->setPosition($position);
        $note->setBlocks($blocks);

        $createdAt = new ReflectionProperty(AbstractBlockNote::class, 'createdAt');
        $createdAt->setValue($note, new DateTimeImmutable('2026-01-15T10:30:00+00:00'));
        $updatedAt = new ReflectionProperty(AbstractBlockNote::class, 'updatedAt');
        $updatedAt->setValue($note, new DateTimeImmutable('2026-01-15T11:00:00+00:00'));

        return $note;
    }
}
