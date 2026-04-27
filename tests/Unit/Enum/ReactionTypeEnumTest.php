<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Enum;

use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use PHPUnit\Framework\TestCase;

final class ReactionTypeEnumTest extends TestCase
{
    public function testAllCasesHaveEmoji(): void
    {
        foreach (ReactionTypeEnum::cases() as $case) {
            self::assertNotEmpty($case->emoji(), sprintf('Emoji for %s must not be empty', $case->name));
        }
    }

    public function testEmojiAreDistinct(): void
    {
        $emojis = array_map(static fn (ReactionTypeEnum $case): string => $case->emoji(), ReactionTypeEnum::cases());

        self::assertSame(count($emojis), count(array_unique($emojis)), 'All emoji must be distinct');
    }
}
