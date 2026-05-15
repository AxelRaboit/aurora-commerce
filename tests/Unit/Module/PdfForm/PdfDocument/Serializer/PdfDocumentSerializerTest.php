<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\PdfDocument\Serializer;

use Aurora\Module\PdfForm\Enum\PdfDocumentStatusEnum;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;
use Aurora\Module\PdfForm\PdfDocument\Serializer\PdfDocumentSerializer;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PdfDocumentSerializerTest extends TestCase
{
    private function makeDocument(?PdfTemplateInterface $template = null, ?string $filePath = null): PdfDocumentInterface
    {
        $doc = $this->createStub(PdfDocumentInterface::class);
        $doc->method('getId')->willReturn(1);
        $doc->method('getReference')->willReturn('PDF-001');
        $doc->method('getLabel')->willReturn('Contract');
        $doc->method('getStatus')->willReturn(PdfDocumentStatusEnum::Draft);
        $doc->method('getTemplate')->willReturn($template);
        $doc->method('getFieldValues')->willReturn(['name' => 'Jane']);
        $doc->method('getContextType')->willReturn(null);
        $doc->method('getContextId')->willReturn(null);
        $doc->method('getFilePath')->willReturn($filePath);
        $doc->method('getCreatedAt')->willReturn(new DateTimeImmutable('2026-01-01'));
        $doc->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2026-01-02'));

        return $doc;
    }

    public function testSerializeWithoutTemplate(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        $result = (new PdfDocumentSerializer($translator, $urlGenerator))->serialize($this->makeDocument());

        self::assertSame(1, $result['id']);
        self::assertSame('PDF-001', $result['reference']);
        self::assertSame('Contract', $result['label']);
        self::assertNull($result['templateId']);
        self::assertNull($result['templateName']);
        self::assertNull($result['downloadUrl']);
    }

    public function testSerializeIncludesTemplate(): void
    {
        $template = $this->createStub(PdfTemplateInterface::class);
        $template->method('getId')->willReturn(7);
        $template->method('getName')->willReturn('Standard Contract');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        $result = (new PdfDocumentSerializer($translator, $urlGenerator))->serialize($this->makeDocument(template: $template));

        self::assertSame(7, $result['templateId']);
        self::assertSame('Standard Contract', $result['templateName']);
    }

    public function testSerializeIncludesDownloadUrlWhenFilePresent(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/download/1');

        $result = (new PdfDocumentSerializer($translator, $urlGenerator))->serialize($this->makeDocument(filePath: '/path/to/file.pdf'));

        self::assertSame('/download/1', $result['downloadUrl']);
    }
}
