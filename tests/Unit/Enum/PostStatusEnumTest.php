<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\PostStatusEnum;
use PHPUnit\Framework\TestCase;

final class PostStatusEnumTest extends TestCase
{
    public function testValuesReturnsAllCasesInDeclarationOrder(): void
    {
        self::assertSame(
            ['draft', 'pending_review', 'scheduled', 'published', 'archived', 'trash'],
            PostStatusEnum::values(),
        );
    }

    public function testTryFromRecognisesAllBackedValues(): void
    {
        foreach (PostStatusEnum::values() as $value) {
            self::assertInstanceOf(PostStatusEnum::class, PostStatusEnum::tryFrom($value));
        }
    }

    public function testTryFromReturnsNullForUnknownValue(): void
    {
        self::assertNull(PostStatusEnum::tryFrom('bogus'));
    }
}
