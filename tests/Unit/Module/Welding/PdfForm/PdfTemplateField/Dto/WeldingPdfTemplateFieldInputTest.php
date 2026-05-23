<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm\PdfTemplateField\Dto;

use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use Aurora\Module\Welding\PdfTemplateField\Dto\WeldingPdfTemplateFieldInput;
use PHPUnit\Framework\TestCase;

final class WeldingPdfTemplateFieldInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new WeldingPdfTemplateFieldInput();

        self::assertSame('', $input->getPdfFieldName());
        self::assertSame('', $input->getLabel());
        self::assertSame(WeldingPdfFieldTypeEnum::Text, $input->getFieldType());
        self::assertNull($input->getMappingKey());
        self::assertNull($input->getDefaultValue());
        self::assertSame(0, $input->getPosition());
    }

    public function testConstructorValues(): void
    {
        $input = new WeldingPdfTemplateFieldInput(
            pdfFieldName: 'contract_signature',
            label: 'Signature',
            fieldType: WeldingPdfFieldTypeEnum::Signature,
            mappingKey: 'user.signature',
            defaultValue: '',
            position: 3,
        );

        self::assertSame('contract_signature', $input->getPdfFieldName());
        self::assertSame('Signature', $input->getLabel());
        self::assertSame(WeldingPdfFieldTypeEnum::Signature, $input->getFieldType());
        self::assertSame('user.signature', $input->getMappingKey());
        self::assertSame('', $input->getDefaultValue());
        self::assertSame(3, $input->getPosition());
    }
}
