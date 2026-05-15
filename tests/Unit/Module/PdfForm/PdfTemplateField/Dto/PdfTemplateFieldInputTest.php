<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\PdfTemplateField\Dto;

use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use Aurora\Module\PdfForm\PdfTemplateField\Dto\PdfTemplateFieldInput;
use PHPUnit\Framework\TestCase;

final class PdfTemplateFieldInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new PdfTemplateFieldInput();

        self::assertSame('', $input->getPdfFieldName());
        self::assertSame('', $input->getLabel());
        self::assertSame(PdfFieldTypeEnum::Text, $input->getFieldType());
        self::assertNull($input->getMappingKey());
        self::assertNull($input->getDefaultValue());
        self::assertSame(0, $input->getPosition());
    }

    public function testConstructorValues(): void
    {
        $input = new PdfTemplateFieldInput(
            pdfFieldName: 'contract_signature',
            label: 'Signature',
            fieldType: PdfFieldTypeEnum::Signature,
            mappingKey: 'user.signature',
            defaultValue: '',
            position: 3,
        );

        self::assertSame('contract_signature', $input->getPdfFieldName());
        self::assertSame('Signature', $input->getLabel());
        self::assertSame(PdfFieldTypeEnum::Signature, $input->getFieldType());
        self::assertSame('user.signature', $input->getMappingKey());
        self::assertSame('', $input->getDefaultValue());
        self::assertSame(3, $input->getPosition());
    }
}
