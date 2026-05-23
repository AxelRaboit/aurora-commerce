<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm;

use Aurora\Module\Welding\Enum\WeldingPdfDocumentStatusEnum;
use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocument;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use PHPUnit\Framework\TestCase;

final class WeldingPdfDocumentTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new WeldingPdfDocument())->getId());
    }

    public function testDefaultValues(): void
    {
        $document = new WeldingPdfDocument();

        self::assertNull($document->getReference());
        self::assertNull($document->getTemplate());
        self::assertSame(WeldingPdfDocumentStatusEnum::Draft, $document->getStatus());
        self::assertNull($document->getLabel());
        self::assertSame([], $document->getFieldValues());
        self::assertNull($document->getContextType());
        self::assertNull($document->getContextId());
        self::assertNull($document->getFilePath());
    }

    public function testTemplateGetterAndSetter(): void
    {
        $template = new WeldingPdfTemplate();
        $document = (new WeldingPdfDocument())->setTemplate($template);

        self::assertSame($template, $document->getTemplate());

        $document->setTemplate(null);
        self::assertNull($document->getTemplate());
    }

    public function testStatusGetterAndSetter(): void
    {
        $document = (new WeldingPdfDocument())->setStatus(WeldingPdfDocumentStatusEnum::Generated);

        self::assertSame(WeldingPdfDocumentStatusEnum::Generated, $document->getStatus());
    }

    public function testLabelGetterAndSetter(): void
    {
        $document = (new WeldingPdfDocument())->setLabel('Q1 Contract');

        self::assertSame('Q1 Contract', $document->getLabel());

        $document->setLabel(null);
        self::assertNull($document->getLabel());
    }

    public function testFieldValuesGetterAndSetter(): void
    {
        $values = ['name' => 'John', 'date' => '2026-01-15'];
        $document = (new WeldingPdfDocument())->setFieldValues($values);

        self::assertSame($values, $document->getFieldValues());
    }

    public function testContextGettersAndSetters(): void
    {
        $document = (new WeldingPdfDocument())->setContextType('project_task')->setContextId(42);

        self::assertSame('project_task', $document->getContextType());
        self::assertSame(42, $document->getContextId());

        $document->setContextType(null);
        $document->setContextId(null);
        self::assertNull($document->getContextType());
        self::assertNull($document->getContextId());
    }

    public function testFilePathGetterAndSetter(): void
    {
        $document = (new WeldingPdfDocument())->setFilePath('/path/to/file.pdf');

        self::assertSame('/path/to/file.pdf', $document->getFilePath());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $document = (new WeldingPdfDocument())->setReference('PDF-001');

        self::assertSame('PDF-001', $document->getReference());
    }
}
