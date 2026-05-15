<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\Document\Dto;

use Aurora\Module\Ged\Document\Dto\DocumentInput;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use PHPUnit\Framework\TestCase;

final class DocumentInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new DocumentInput();

        self::assertSame('', $input->getTitle());
        self::assertNull($input->getDescription());
        self::assertSame(DocumentStatusEnum::Draft, $input->getStatus());
        self::assertNull($input->getCategoryId());
        self::assertNull($input->getFileId());
        self::assertSame([], $input->getTagIds());
        self::assertNull($input->getFolderId());
    }

    public function testConstructorValues(): void
    {
        $input = new DocumentInput(
            title: 'Annual Report',
            description: 'Q1 report',
            status: DocumentStatusEnum::Published,
            categoryId: 1,
            fileId: 99,
            tagIds: [1, 2, 3],
            folderId: 7,
        );

        self::assertSame('Annual Report', $input->getTitle());
        self::assertSame('Q1 report', $input->getDescription());
        self::assertSame(DocumentStatusEnum::Published, $input->getStatus());
        self::assertSame(1, $input->getCategoryId());
        self::assertSame(99, $input->getFileId());
        self::assertSame([1, 2, 3], $input->getTagIds());
        self::assertSame(7, $input->getFolderId());
    }
}
