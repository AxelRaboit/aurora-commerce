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
        self::assertNull($input->getFilePath());
        self::assertNull($input->getFileName());
        self::assertNull($input->getOriginalName());
        self::assertNull($input->getMimeType());
        self::assertNull($input->getSize());
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
            filePath: 'ged/2026/05/file.pdf',
            fileName: 'file.pdf',
            originalName: 'Original.pdf',
            mimeType: 'application/pdf',
            size: 4567,
            tagIds: [1, 2, 3],
            folderId: 7,
        );

        self::assertSame('Annual Report', $input->getTitle());
        self::assertSame('Q1 report', $input->getDescription());
        self::assertSame(DocumentStatusEnum::Published, $input->getStatus());
        self::assertSame(1, $input->getCategoryId());
        self::assertSame('ged/2026/05/file.pdf', $input->getFilePath());
        self::assertSame('file.pdf', $input->getFileName());
        self::assertSame('Original.pdf', $input->getOriginalName());
        self::assertSame('application/pdf', $input->getMimeType());
        self::assertSame(4567, $input->getSize());
        self::assertSame([1, 2, 3], $input->getTagIds());
        self::assertSame(7, $input->getFolderId());
    }
}
