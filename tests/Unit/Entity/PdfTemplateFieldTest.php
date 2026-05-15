<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateField;
use PHPUnit\Framework\TestCase;

final class PdfTemplateFieldTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new PdfTemplateField())->getId());
    }

    public function testDefaultValues(): void
    {
        $field = new PdfTemplateField();

        self::assertSame(PdfFieldTypeEnum::Text, $field->getFieldType());
        self::assertNull($field->getMappingKey());
        self::assertNull($field->getDefaultValue());
        self::assertSame(0, $field->getPosition());
    }

    public function testTemplateGetterAndSetter(): void
    {
        $template = new PdfTemplate();
        $field = (new PdfTemplateField())->setTemplate($template);

        self::assertSame($template, $field->getTemplate());
    }

    public function testPdfFieldNameAndLabelGettersAndSetters(): void
    {
        $field = (new PdfTemplateField())
            ->setPdfFieldName('contract_signature')
            ->setLabel('Signature');

        self::assertSame('contract_signature', $field->getPdfFieldName());
        self::assertSame('Signature', $field->getLabel());
    }

    public function testFieldTypeGetterAndSetter(): void
    {
        $field = (new PdfTemplateField())->setFieldType(PdfFieldTypeEnum::Signature);

        self::assertSame(PdfFieldTypeEnum::Signature, $field->getFieldType());
    }

    public function testMappingKeyAndDefaultValueGettersAndSetters(): void
    {
        $field = (new PdfTemplateField())->setMappingKey('user.name')->setDefaultValue('N/A');

        self::assertSame('user.name', $field->getMappingKey());
        self::assertSame('N/A', $field->getDefaultValue());

        $field->setMappingKey(null);
        $field->setDefaultValue(null);
        self::assertNull($field->getMappingKey());
        self::assertNull($field->getDefaultValue());
    }

    public function testPositionGetterAndSetter(): void
    {
        $field = (new PdfTemplateField())->setPosition(3);

        self::assertSame(3, $field->getPosition());
    }
}
