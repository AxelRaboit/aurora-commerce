<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\Enum;

use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use PHPUnit\Framework\TestCase;

final class PdfFieldTypeEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.pdfform.fields.type_text', PdfFieldTypeEnum::Text->getLabelKey());
        self::assertSame('backend.pdfform.fields.type_signature', PdfFieldTypeEnum::Signature->getLabelKey());
    }

    public function testCases(): void
    {
        self::assertSame('text', PdfFieldTypeEnum::Text->value);
        self::assertSame('checkbox', PdfFieldTypeEnum::Checkbox->value);
        self::assertSame('signature', PdfFieldTypeEnum::Signature->value);
        self::assertCount(6, PdfFieldTypeEnum::cases());
    }
}
