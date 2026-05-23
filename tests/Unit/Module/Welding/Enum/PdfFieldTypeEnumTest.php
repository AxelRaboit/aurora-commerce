<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Enum;

use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use PHPUnit\Framework\TestCase;

final class PdfFieldTypeEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.welding.pdf_template_fields.type_text', WeldingPdfFieldTypeEnum::Text->getLabelKey());
        self::assertSame('backend.welding.pdf_template_fields.type_signature', WeldingPdfFieldTypeEnum::Signature->getLabelKey());
    }

    public function testCases(): void
    {
        self::assertSame('text', WeldingPdfFieldTypeEnum::Text->value);
        self::assertSame('checkbox', WeldingPdfFieldTypeEnum::Checkbox->value);
        self::assertSame('signature', WeldingPdfFieldTypeEnum::Signature->value);
        self::assertCount(6, WeldingPdfFieldTypeEnum::cases());
    }
}
