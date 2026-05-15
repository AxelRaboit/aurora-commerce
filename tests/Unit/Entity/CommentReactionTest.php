<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Entity\CommentReaction;
use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use PHPUnit\Framework\TestCase;

final class CommentReactionTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new CommentReaction())->getId());
    }

    public function testCommentGetterAndSetter(): void
    {
        $comment = $this->createStub(CommentInterface::class);
        $reaction = (new CommentReaction())->setComment($comment);

        self::assertSame($comment, $reaction->getComment());
    }

    public function testTypeGetterAndSetter(): void
    {
        $reaction = (new CommentReaction())->setType(ReactionTypeEnum::Like);

        self::assertSame(ReactionTypeEnum::Like, $reaction->getType());
    }

    public function testFingerprintGetterAndSetter(): void
    {
        $reaction = (new CommentReaction())->setFingerprint('fp-abc123');

        self::assertSame('fp-abc123', $reaction->getFingerprint());
    }

    public function testSettersReturnSelf(): void
    {
        $reaction = new CommentReaction();

        self::assertSame($reaction, $reaction->setComment($this->createStub(CommentInterface::class)));
        self::assertSame($reaction, $reaction->setType(ReactionTypeEnum::Love));
        self::assertSame($reaction, $reaction->setFingerprint('fp'));
    }
}
