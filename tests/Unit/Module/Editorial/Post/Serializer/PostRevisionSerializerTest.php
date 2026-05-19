<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Serializer;

use Aurora\Module\Editorial\Post\Entity\PostRevisionInterface;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Serializer\PostRevisionSerializer;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class PostRevisionSerializerTest extends TestCase
{
    private function makeAuthor(int $id): User
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);
        $user->setEmail('author@example.com');

        return $user;
    }

    public function testSerializeWithAuthor(): void
    {
        $revision = $this->createStub(PostRevisionInterface::class);
        $revision->method('getId')->willReturn(1);
        $revision->method('getPostVersion')->willReturn(5);
        $revision->method('getStatus')->willReturn(PostStatusEnum::Published);
        $revision->method('getCreatedAtImmutable')->willReturn(new DateTimeImmutable('2026-01-15T10:00:00+00:00'));
        $revision->method('getAuthor')->willReturn($this->makeAuthor(7));

        $result = (new PostRevisionSerializer())->serialize($revision);

        self::assertSame(1, $result['id']);
        self::assertSame(5, $result['postVersion']);
        self::assertSame('published', $result['status']);
        self::assertSame('2026-01-15T10:00:00+00:00', $result['createdAt']);
        self::assertSame(['id' => 7, 'email' => 'author@example.com'], $result['author']);
    }

    public function testSerializeWithNullAuthor(): void
    {
        $revision = $this->createStub(PostRevisionInterface::class);
        $revision->method('getId')->willReturn(1);
        $revision->method('getPostVersion')->willReturn(1);
        $revision->method('getStatus')->willReturn(PostStatusEnum::Draft);
        $revision->method('getCreatedAtImmutable')->willReturn(new DateTimeImmutable());
        $revision->method('getAuthor')->willReturn(null);

        $result = (new PostRevisionSerializer())->serialize($revision);

        self::assertNull($result['author']);
    }

    public function testSerializeFullIncludesSnapshot(): void
    {
        $snapshot = ['title' => 'Hello'];

        $revision = $this->createStub(PostRevisionInterface::class);
        $revision->method('getId')->willReturn(1);
        $revision->method('getPostVersion')->willReturn(1);
        $revision->method('getStatus')->willReturn(PostStatusEnum::Draft);
        $revision->method('getCreatedAtImmutable')->willReturn(new DateTimeImmutable());
        $revision->method('getAuthor')->willReturn(null);
        $revision->method('getSnapshot')->willReturn($snapshot);

        $result = (new PostRevisionSerializer())->serializeFull($revision);

        self::assertSame($snapshot, $result['snapshot']);
        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('postVersion', $result);
    }
}
