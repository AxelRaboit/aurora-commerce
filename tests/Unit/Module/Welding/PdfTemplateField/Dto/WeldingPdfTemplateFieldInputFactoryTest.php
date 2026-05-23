<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfTemplateField\Dto;

use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use Aurora\Module\Welding\PdfTemplateField\Dto\WeldingPdfTemplateFieldInputFactory;
use PHPUnit\Framework\TestCase;

final class WeldingPdfTemplateFieldInputFactoryTest extends TestCase
{
    public function testFromArrayParsesAllFields(): void
    {
        $input = (new WeldingPdfTemplateFieldInputFactory())->fromArray([
            'pdfFieldName' => '  contract_signature  ',
            'label' => '  Signature  ',
            'fieldType' => 'signature',
            'mappingKey' => '  user.signature  ',
            'defaultValue' => '  default  ',
            'position' => '3',
        ]);

        self::assertSame('contract_signature', $input->getPdfFieldName());
        self::assertSame('Signature', $input->getLabel());
        self::assertSame(WeldingPdfFieldTypeEnum::Signature, $input->getFieldType());
        self::assertSame('user.signature', $input->getMappingKey());
        self::assertSame('default', $input->getDefaultValue());
        self::assertSame(3, $input->getPosition());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new WeldingPdfTemplateFieldInputFactory())->fromArray([]);

        self::assertSame(WeldingPdfFieldTypeEnum::Text, $input->getFieldType());
        self::assertSame(0, $input->getPosition());
    }

    public function testFromArrayWithInvalidFieldTypeFallsBackToText(): void
    {
        $input = (new WeldingPdfTemplateFieldInputFactory())->fromArray(['fieldType' => 'invalid']);

        self::assertSame(WeldingPdfFieldTypeEnum::Text, $input->getFieldType());
    }
}
