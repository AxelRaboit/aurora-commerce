<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\PostIt\Serializer;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Notes\PostIt\Entity\PostItNote;
use Aurora\Module\Notes\PostIt\Serializer\PostItNoteSerializer;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

final class PostItNoteSerializerTest extends TestCase
{
    private PostItNoteSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new PostItNoteSerializer();
    }

    public function testSerializesAllFields(): void
    {
        $note = $this->makeNote(
            id: 12,
            title: 'Shopping',
            content: 'milk',
            color: '#90CAF9',
            positionX: 50,
            positionY: 120,
            width: 200,
            height: 240,
        );

        $payload = $this->serializer->serialize($note);

        self::assertSame(12, $payload['id']);
        self::assertSame('Shopping', $payload['title']);
        self::assertSame('milk', $payload['content']);
        self::assertSame('#90CAF9', $payload['color']);
        self::assertSame(50, $payload['positionX']);
        self::assertSame(120, $payload['positionY']);
        self::assertSame(200, $payload['width']);
        self::assertSame(240, $payload['height']);
    }

    public function testTimestampsAreSerializedAsAtom(): void
    {
        $note = $this->makeNote();

        $payload = $this->serializer->serialize($note);

        self::assertSame(
            (new DateTimeImmutable('2026-01-15T10:30:00+00:00'))->format(DateTimeInterface::ATOM),
            $payload['createdAt'],
        );
        self::assertSame(
            (new DateTimeImmutable('2026-01-15T11:00:00+00:00'))->format(DateTimeInterface::ATOM),
            $payload['updatedAt'],
        );
    }

    public function testNullableFieldsRemainNull(): void
    {
        $note = $this->makeNote(title: null, content: null);

        $payload = $this->serializer->serialize($note);

        self::assertNull($payload['title']);
        self::assertNull($payload['content']);
    }

    public function testDefaultColorFromEntityDefault(): void
    {
        $note = new PostItNote();
        $this->setId($note, 1);
        $this->setTimestamps($note);

        $payload = $this->serializer->serialize($note);

        // Entity default — sticky-note yellow.
        self::assertSame('#FFEB3B', $payload['color']);
    }

    private function makeNote(
        ?int $id = 1,
        ?string $title = 'Title',
        ?string $content = 'Body',
        string $color = '#FFEB3B',
        int $positionX = 0,
        int $positionY = 0,
        int $width = 220,
        int $height = 220,
    ): PostItNote {
        $note = new PostItNote();
        if (null !== $id) {
            $this->setId($note, $id);
        }
        $note->setTitle($title);
        $note->setContent($content);
        $note->setColor($color);
        $note->setPositionX($positionX);
        $note->setPositionY($positionY);
        $note->setWidth($width);
        $note->setHeight($height);
        $this->setTimestamps($note);

        return $note;
    }

    private function setId(PostItNote $note, int $id): void
    {
        $r = new ReflectionProperty(PostItNote::class, 'id');
        $r->setValue($note, $id);
    }

    /**
     * Timestamps live on the Timestampable trait which is mixed in via the
     * Abstract parent — reflect on the trait property names directly.
     */
    private function setTimestamps(PostItNote $note): void
    {
        $createdAt = $this->findTraitProperty($note, 'createdAt');
        $createdAt->setValue($note, new DateTimeImmutable('2026-01-15T10:30:00+00:00'));

        $updatedAt = $this->findTraitProperty($note, 'updatedAt');
        $updatedAt->setValue($note, new DateTimeImmutable('2026-01-15T11:00:00+00:00'));
    }

    private function findTraitProperty(PostItNote $note, string $name): ReflectionProperty
    {
        // TimestampableTrait — used by AbstractPostItNote — declares the
        // properties on the host class, but reflection finds them via the
        // class hierarchy regardless of trait origin.
        $class = $note::class;
        while (false !== $class) {
            try {
                $r = new ReflectionProperty($class, $name);

                return $r;
            } catch (ReflectionException) {
                $class = get_parent_class($class);
            }
        }
        throw new LogicException(sprintf('Property %s not found on %s hierarchy.', $name, $note::class));
    }
}
