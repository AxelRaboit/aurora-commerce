<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Comment\Dto;

use Aurora\Module\Editorial\Comment\Dto\CommentInputFactory;
use PHPUnit\Framework\TestCase;

final class CommentInputFactoryTest extends TestCase
{
    public function testFromArrayParsesAllFields(): void
    {
        $input = (new CommentInputFactory())->fromArray([
            'authorName' => '  Jane  ',
            'authorEmail' => '  jane@example.com  ',
            'content' => '  Hello  ',
            'parentId' => 5,
        ]);

        self::assertSame('Jane', $input->getAuthorName());
        self::assertSame('jane@example.com', $input->getAuthorEmail());
        self::assertSame('Hello', $input->getContent());
        self::assertSame(5, $input->getParentId());
    }

    public function testFromArrayUsesSnakeCaseParentId(): void
    {
        $input = (new CommentInputFactory())->fromArray([
            'authorName' => 'X',
            'authorEmail' => 'x@x.com',
            'content' => 'Y',
            'parent_id' => 3,
        ]);

        self::assertSame(3, $input->getParentId());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new CommentInputFactory())->fromArray([]);

        self::assertSame('', $input->getAuthorName());
        self::assertSame('', $input->getAuthorEmail());
        self::assertSame('', $input->getContent());
        self::assertNull($input->getParentId());
    }
}
