<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Markdown\Serializer;

use Aurora\Module\Notes\Markdown\Entity\AbstractMarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Serializer\MarkdownNoteSerializer;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class MarkdownNoteSerializerTest extends TestCase
{
    private MarkdownNoteSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new MarkdownNoteSerializer();
    }

    public function testListItemOmitsContent(): void
    {
        $note = $this->makeNote(id: 7, title: 'Hello', content: 'should be hidden');

        $payload = $this->serializer->serializeListItem($note);

        self::assertArrayNotHasKey('content', $payload);
        self::assertSame(7, $payload['id']);
        self::assertSame('Hello', $payload['title']);
    }

    public function testDetailIncludesContent(): void
    {
        $note = $this->makeNote(id: 7, title: 'Hello', content: '# body');

        $payload = $this->serializer->serializeDetail($note);

        self::assertSame('# body', $payload['content']);
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

    /** @param list<string> $tags */
    private function makeNote(
        ?int $id = 1,
        ?string $title = null,
        ?string $content = null,
        array $tags = [],
        int $position = 0,
    ): MarkdownNote {
        $note = new MarkdownNote();

        if (null !== $id) {
            $r = new ReflectionProperty(MarkdownNote::class, 'id');
            $r->setValue($note, $id);
        }

        $note->setTitle($title);
        $note->setContent($content);
        $note->setTags($tags);
        $note->setPosition($position);

        $createdAt = new ReflectionProperty(AbstractMarkdownNote::class, 'createdAt');
        $createdAt->setValue($note, new DateTimeImmutable('2026-01-15T10:30:00+00:00'));
        $updatedAt = new ReflectionProperty(AbstractMarkdownNote::class, 'updatedAt');
        $updatedAt->setValue($note, new DateTimeImmutable('2026-01-15T11:00:00+00:00'));

        return $note;
    }
}
