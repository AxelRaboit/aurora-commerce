<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Enum;

use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use PHPUnit\Framework\TestCase;

final class CommentStatusEnumTest extends TestCase
{
    public function testAllCasesHaveLabels(): void
    {
        $expectedCases = [CommentStatusEnum::Pending, CommentStatusEnum::Approved, CommentStatusEnum::Spam];

        foreach ($expectedCases as $case) {
            self::assertNotEmpty($case->label(), sprintf('Label for %s must not be empty', $case->name));
        }
    }
}
