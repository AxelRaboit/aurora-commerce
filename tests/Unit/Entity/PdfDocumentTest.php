<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\PdfForm\Enum\PdfDocumentStatusEnum;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocument;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use PHPUnit\Framework\TestCase;

final class PdfDocumentTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new PdfDocument())->getId());
    }

    public function testDefaultValues(): void
    {
        $document = new PdfDocument();

        self::assertNull($document->getReference());
        self::assertNull($document->getTemplate());
        self::assertSame(PdfDocumentStatusEnum::Draft, $document->getStatus());
        self::assertNull($document->getLabel());
        self::assertSame([], $document->getFieldValues());
        self::assertNull($document->getContextType());
        self::assertNull($document->getContextId());
        self::assertNull($document->getFilePath());
    }

    public function testTemplateGetterAndSetter(): void
    {
        $template = new PdfTemplate();
        $document = (new PdfDocument())->setTemplate($template);

        self::assertSame($template, $document->getTemplate());

        $document->setTemplate(null);
        self::assertNull($document->getTemplate());
    }

    public function testStatusGetterAndSetter(): void
    {
        $document = (new PdfDocument())->setStatus(PdfDocumentStatusEnum::Generated);

        self::assertSame(PdfDocumentStatusEnum::Generated, $document->getStatus());
    }

    public function testLabelGetterAndSetter(): void
    {
        $document = (new PdfDocument())->setLabel('Q1 Contract');

        self::assertSame('Q1 Contract', $document->getLabel());

        $document->setLabel(null);
        self::assertNull($document->getLabel());
    }

    public function testFieldValuesGetterAndSetter(): void
    {
        $values = ['name' => 'John', 'date' => '2026-01-15'];
        $document = (new PdfDocument())->setFieldValues($values);

        self::assertSame($values, $document->getFieldValues());
    }

    public function testContextGettersAndSetters(): void
    {
        $document = (new PdfDocument())->setContextType('project_task')->setContextId(42);

        self::assertSame('project_task', $document->getContextType());
        self::assertSame(42, $document->getContextId());

        $document->setContextType(null);
        $document->setContextId(null);
        self::assertNull($document->getContextType());
        self::assertNull($document->getContextId());
    }

    public function testFilePathGetterAndSetter(): void
    {
        $document = (new PdfDocument())->setFilePath('/path/to/file.pdf');

        self::assertSame('/path/to/file.pdf', $document->getFilePath());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $document = (new PdfDocument())->setReference('PDF-001');

        self::assertSame('PDF-001', $document->getReference());
    }
}
