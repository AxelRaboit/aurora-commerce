<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Comment\Enum;

use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use PHPUnit\Framework\TestCase;

final class ReactionTypeEnumTest extends TestCase
{
    public function testEmoji(): void
    {
        self::assertSame('👍', ReactionTypeEnum::Like->emoji());
        self::assertSame('❤️', ReactionTypeEnum::Love->emoji());
        self::assertSame('😂', ReactionTypeEnum::Haha->emoji());
        self::assertSame('😮', ReactionTypeEnum::Wow->emoji());
        self::assertSame('😢', ReactionTypeEnum::Sad->emoji());
        self::assertSame('😡', ReactionTypeEnum::Angry->emoji());
    }

    public function testLabel(): void
    {
        self::assertSame("J'aime", ReactionTypeEnum::Like->label());
        self::assertSame("J'adore", ReactionTypeEnum::Love->label());
        self::assertSame('Haha', ReactionTypeEnum::Haha->label());
        self::assertSame('Waouh', ReactionTypeEnum::Wow->label());
        self::assertSame('Triste', ReactionTypeEnum::Sad->label());
        self::assertSame('En colère', ReactionTypeEnum::Angry->label());
    }
}
