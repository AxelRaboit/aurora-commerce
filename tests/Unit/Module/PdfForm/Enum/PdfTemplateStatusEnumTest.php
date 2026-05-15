<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\Enum;

use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use PHPUnit\Framework\TestCase;

final class PdfTemplateStatusEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.pdfform.templates.status_draft', PdfTemplateStatusEnum::Draft->getLabelKey());
        self::assertSame('backend.pdfform.templates.status_active', PdfTemplateStatusEnum::Active->getLabelKey());
        self::assertSame('backend.pdfform.templates.status_archived', PdfTemplateStatusEnum::Archived->getLabelKey());
    }
}
