<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm\Serializer;

use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Aurora\Module\Welding\PdfTemplate\Serializer\WeldingPdfTemplateSerializer;
use Aurora\Module\Welding\PdfTemplateField\Serializer\WeldingPdfTemplateFieldSerializerInterface;
use Aurora\Tests\Concern\CreatesStorageUrlGenerators;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WeldingPdfTemplateSerializerTest extends TestCase
{
    use CreatesStorageUrlGenerators;

    private WeldingPdfTemplateSerializer $serializer;

    protected function setUp(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $fieldSerializer = $this->createStub(WeldingPdfTemplateFieldSerializerInterface::class);
        $fieldSerializer->method('serialize')->willReturn([]);

        $this->serializer = new WeldingPdfTemplateSerializer($translator, $fieldSerializer, $this->makeMediaUrlGenerator());
    }

    private function makeTemplateStub(bool $requiresSignature = false, bool $flattenOnGenerate = false): WeldingPdfTemplateInterface
    {
        $template = $this->createStub(WeldingPdfTemplateInterface::class);
        $template->method('getId')->willReturn(1);
        $template->method('getName')->willReturn('Contrat');
        $template->method('getDescription')->willReturn(null);
        $template->method('getStatus')->willReturn(WeldingPdfTemplateStatusEnum::Active);
        $template->method('getFile')->willReturn(null);
        $template->method('isFlattenOnGenerate')->willReturn($flattenOnGenerate);
        $template->method('isRequiresSignature')->willReturn($requiresSignature);
        $template->method('getFields')->willReturn(new ArrayCollection());
        $template->method('getCreatedAt')->willReturn(new DateTimeImmutable('2026-01-01'));
        $template->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2026-01-01'));

        return $template;
    }

    public function testSerializeIncludesRequiresSignatureTrue(): void
    {
        $result = $this->serializer->serialize($this->makeTemplateStub(requiresSignature: true));

        self::assertTrue($result['requiresSignature']);
    }

    public function testSerializeIncludesRequiresSignatureFalse(): void
    {
        $result = $this->serializer->serialize($this->makeTemplateStub(requiresSignature: false));

        self::assertFalse($result['requiresSignature']);
    }

    public function testSerializeIncludesFlattenOnGenerate(): void
    {
        $result = $this->serializer->serialize($this->makeTemplateStub(flattenOnGenerate: true));

        self::assertTrue($result['flattenOnGenerate']);
    }

    public function testSerializeIncludesExpectedKeys(): void
    {
        $result = $this->serializer->serialize($this->makeTemplateStub());

        foreach (['id', 'name', 'status', 'statusLabel', 'flattenOnGenerate', 'requiresSignature', 'fieldCount', 'fields', 'createdAt', 'updatedAt'] as $key) {
            self::assertArrayHasKey($key, $result, "Missing key: $key");
        }
    }

    public function testSerializeStatusLabelIsTranslated(): void
    {
        $result = $this->serializer->serialize($this->makeTemplateStub());

        // Stub returns the translation key as-is.
        self::assertSame(WeldingPdfTemplateStatusEnum::Active->getLabelKey(), $result['statusLabel']);
    }
}
