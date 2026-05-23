<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm;

use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateField;
use PHPUnit\Framework\TestCase;

final class WeldingPdfTemplateFieldTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new WeldingPdfTemplateField())->getId());
    }

    public function testDefaultValues(): void
    {
        $field = new WeldingPdfTemplateField();

        self::assertSame(WeldingPdfFieldTypeEnum::Text, $field->getFieldType());
        self::assertNull($field->getMappingKey());
        self::assertNull($field->getDefaultValue());
        self::assertSame(0, $field->getPosition());
    }

    public function testTemplateGetterAndSetter(): void
    {
        $template = new WeldingPdfTemplate();
        $field = (new WeldingPdfTemplateField())->setTemplate($template);

        self::assertSame($template, $field->getTemplate());
    }

    public function testPdfFieldNameAndLabelGettersAndSetters(): void
    {
        $field = (new WeldingPdfTemplateField())
            ->setPdfFieldName('contract_signature')
            ->setLabel('Signature');

        self::assertSame('contract_signature', $field->getPdfFieldName());
        self::assertSame('Signature', $field->getLabel());
    }

    public function testFieldTypeGetterAndSetter(): void
    {
        $field = (new WeldingPdfTemplateField())->setFieldType(WeldingPdfFieldTypeEnum::Signature);

        self::assertSame(WeldingPdfFieldTypeEnum::Signature, $field->getFieldType());
    }

    public function testMappingKeyAndDefaultValueGettersAndSetters(): void
    {
        $field = (new WeldingPdfTemplateField())->setMappingKey('user.name')->setDefaultValue('N/A');

        self::assertSame('user.name', $field->getMappingKey());
        self::assertSame('N/A', $field->getDefaultValue());

        $field->setMappingKey(null);
        $field->setDefaultValue(null);
        self::assertNull($field->getMappingKey());
        self::assertNull($field->getDefaultValue());
    }

    public function testPositionGetterAndSetter(): void
    {
        $field = (new WeldingPdfTemplateField())->setPosition(3);

        self::assertSame(3, $field->getPosition());
    }
}
