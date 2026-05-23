<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use PHPUnit\Framework\TestCase;

final class WeldingPdfTemplateTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new WeldingPdfTemplate())->getId());
    }

    public function testFieldsCollectionInitialized(): void
    {
        self::assertCount(0, (new WeldingPdfTemplate())->getFields());
    }

    public function testDefaultValues(): void
    {
        $template = new WeldingPdfTemplate();

        self::assertSame(WeldingPdfTemplateStatusEnum::Draft, $template->getStatus());
        self::assertNull($template->getDescription());
        self::assertNull($template->getFile());
        self::assertFalse($template->isFlattenOnGenerate());
        self::assertFalse($template->isRequiresSignature());
    }

    public function testNameGetterAndSetter(): void
    {
        $template = (new WeldingPdfTemplate())->setName('Contract Template');

        self::assertSame('Contract Template', $template->getName());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $template = (new WeldingPdfTemplate())->setDescription('Standard contract.');

        self::assertSame('Standard contract.', $template->getDescription());

        $template->setDescription(null);
        self::assertNull($template->getDescription());
    }

    public function testStatusGetterAndSetter(): void
    {
        $template = (new WeldingPdfTemplate())->setStatus(WeldingPdfTemplateStatusEnum::Active);

        self::assertSame(WeldingPdfTemplateStatusEnum::Active, $template->getStatus());
    }

    public function testFileGetterAndSetter(): void
    {
        $file = $this->createStub(MediaInterface::class);
        $template = (new WeldingPdfTemplate())->setFile($file);

        self::assertSame($file, $template->getFile());

        $template->setFile(null);
        self::assertNull($template->getFile());
    }

    public function testFlattenOnGenerateGetterAndSetter(): void
    {
        $template = (new WeldingPdfTemplate())->setFlattenOnGenerate(true);

        self::assertTrue($template->isFlattenOnGenerate());
    }

    public function testRequiresSignatureGetterAndSetter(): void
    {
        $template = (new WeldingPdfTemplate())->setRequiresSignature(true);

        self::assertTrue($template->isRequiresSignature());
    }

    public function testSettersReturnSelf(): void
    {
        $template = new WeldingPdfTemplate();

        self::assertSame($template, $template->setName('n'));
        self::assertSame($template, $template->setDescription('d'));
        self::assertSame($template, $template->setStatus(WeldingPdfTemplateStatusEnum::Archived));
        self::assertSame($template, $template->setFile($this->createStub(MediaInterface::class)));
        self::assertSame($template, $template->setFlattenOnGenerate(true));
        self::assertSame($template, $template->setRequiresSignature(true));
    }
}
