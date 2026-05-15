<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\PdfTemplateField\Dto;

use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use Aurora\Module\PdfForm\PdfTemplateField\Dto\PdfTemplateFieldInputFactory;
use PHPUnit\Framework\TestCase;

final class PdfTemplateFieldInputFactoryTest extends TestCase
{
    public function testFromArrayParsesAllFields(): void
    {
        $input = (new PdfTemplateFieldInputFactory())->fromArray([
            'pdfFieldName' => '  contract_signature  ',
            'label' => '  Signature  ',
            'fieldType' => 'signature',
            'mappingKey' => '  user.signature  ',
            'defaultValue' => '  default  ',
            'position' => '3',
        ]);

        self::assertSame('contract_signature', $input->getPdfFieldName());
        self::assertSame('Signature', $input->getLabel());
        self::assertSame(PdfFieldTypeEnum::Signature, $input->getFieldType());
        self::assertSame('user.signature', $input->getMappingKey());
        self::assertSame('default', $input->getDefaultValue());
        self::assertSame(3, $input->getPosition());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new PdfTemplateFieldInputFactory())->fromArray([]);

        self::assertSame(PdfFieldTypeEnum::Text, $input->getFieldType());
        self::assertSame(0, $input->getPosition());
    }

    public function testFromArrayWithInvalidFieldTypeFallsBackToText(): void
    {
        $input = (new PdfTemplateFieldInputFactory())->fromArray(['fieldType' => 'invalid']);

        self::assertSame(PdfFieldTypeEnum::Text, $input->getFieldType());
    }
}
