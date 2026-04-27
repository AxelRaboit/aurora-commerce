<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CommentTest extends TestCase
{
    public function testDefaultStatusIsPending(): void
    {
        $comment = new Comment();

        self::assertSame(CommentStatusEnum::Pending, $comment->getStatus());
    }

    public function testCreatedAtSetInConstructor(): void
    {
        $comment = new Comment();

        self::assertInstanceOf(DateTimeImmutable::class, $comment->getCreatedAt());
    }

    public function testParentNullByDefault(): void
    {
        $comment = new Comment();

        self::assertNull($comment->getParent());
    }

    public function testSetParentAndRetrieve(): void
    {
        $parent = new Comment();
        $child = new Comment();
        $child->setParent($parent);

        self::assertSame($parent, $child->getParent());
    }
}
