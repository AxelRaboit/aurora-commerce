<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Serializer;

use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectTaskCommentInterface;
use Aurora\Module\Project\Serializer\ProjectTaskCommentSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ProjectTaskCommentSerializerTest extends TestCase
{
    public function testSerializeReturnsExpectedShape(): void
    {
        $author = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($author, 5);
        $author->setName('Author');

        $comment = $this->createStub(ProjectTaskCommentInterface::class);
        $comment->method('getId')->willReturn(10);
        $comment->method('getContent')->willReturn('Great work');
        $comment->method('getAuthor')->willReturn($author);
        $comment->method('getCreatedAt')->willReturn(new DateTimeImmutable('2026-01-15T10:00:00+00:00'));

        $result = (new ProjectTaskCommentSerializer())->serialize($comment);

        self::assertSame(10, $result['id']);
        self::assertSame('Great work', $result['content']);
        self::assertSame(5, $result['authorId']);
        self::assertSame('Author', $result['authorName']);
        self::assertSame('2026-01-15T10:00:00+00:00', $result['createdAt']);
    }
}
