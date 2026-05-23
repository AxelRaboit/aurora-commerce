<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Enum;

use Aurora\Module\Welding\Enum\WeldingPdfDocumentStatusEnum;
use PHPUnit\Framework\TestCase;

final class WeldingPdfDocumentStatusEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.welding.pdf_documents.status_draft', WeldingPdfDocumentStatusEnum::Draft->getLabelKey());
        self::assertSame('backend.welding.pdf_documents.status_generated', WeldingPdfDocumentStatusEnum::Generated->getLabelKey());
        self::assertSame('backend.welding.pdf_documents.status_archived', WeldingPdfDocumentStatusEnum::Archived->getLabelKey());
    }
}
