<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\Enum;

use Aurora\Module\PdfForm\Enum\PdfDocumentStatusEnum;
use PHPUnit\Framework\TestCase;

final class PdfDocumentStatusEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.pdfform.documents.status_draft', PdfDocumentStatusEnum::Draft->getLabelKey());
        self::assertSame('backend.pdfform.documents.status_generated', PdfDocumentStatusEnum::Generated->getLabelKey());
        self::assertSame('backend.pdfform.documents.status_archived', PdfDocumentStatusEnum::Archived->getLabelKey());
    }
}
