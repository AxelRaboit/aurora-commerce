<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Comment\Dto;

use Aurora\Module\Editorial\Comment\Dto\CommentInput;
use PHPUnit\Framework\TestCase;

final class CommentInputTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $input = new CommentInput('Jane', 'jane@example.com', 'Hello', 5);

        self::assertSame('Jane', $input->getAuthorName());
        self::assertSame('jane@example.com', $input->getAuthorEmail());
        self::assertSame('Hello', $input->getContent());
        self::assertSame(5, $input->getParentId());
    }

    public function testParentIdIsNullByDefault(): void
    {
        $input = new CommentInput('Jane', 'jane@x.com', 'Hi');

        self::assertNull($input->getParentId());
    }
}
