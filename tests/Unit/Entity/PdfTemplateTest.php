<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use PHPUnit\Framework\TestCase;

final class PdfTemplateTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new PdfTemplate())->getId());
    }

    public function testFieldsCollectionInitialized(): void
    {
        self::assertCount(0, (new PdfTemplate())->getFields());
    }

    public function testDefaultValues(): void
    {
        $template = new PdfTemplate();

        self::assertSame(PdfTemplateStatusEnum::Draft, $template->getStatus());
        self::assertNull($template->getDescription());
        self::assertNull($template->getFile());
        self::assertFalse($template->isFlattenOnGenerate());
        self::assertFalse($template->isRequiresSignature());
    }

    public function testNameGetterAndSetter(): void
    {
        $template = (new PdfTemplate())->setName('Contract Template');

        self::assertSame('Contract Template', $template->getName());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $template = (new PdfTemplate())->setDescription('Standard contract.');

        self::assertSame('Standard contract.', $template->getDescription());

        $template->setDescription(null);
        self::assertNull($template->getDescription());
    }

    public function testStatusGetterAndSetter(): void
    {
        $template = (new PdfTemplate())->setStatus(PdfTemplateStatusEnum::Active);

        self::assertSame(PdfTemplateStatusEnum::Active, $template->getStatus());
    }

    public function testFileGetterAndSetter(): void
    {
        $file = $this->createStub(MediaInterface::class);
        $template = (new PdfTemplate())->setFile($file);

        self::assertSame($file, $template->getFile());

        $template->setFile(null);
        self::assertNull($template->getFile());
    }

    public function testFlattenOnGenerateGetterAndSetter(): void
    {
        $template = (new PdfTemplate())->setFlattenOnGenerate(true);

        self::assertTrue($template->isFlattenOnGenerate());
    }

    public function testRequiresSignatureGetterAndSetter(): void
    {
        $template = (new PdfTemplate())->setRequiresSignature(true);

        self::assertTrue($template->isRequiresSignature());
    }

    public function testSettersReturnSelf(): void
    {
        $template = new PdfTemplate();

        self::assertSame($template, $template->setName('n'));
        self::assertSame($template, $template->setDescription('d'));
        self::assertSame($template, $template->setStatus(PdfTemplateStatusEnum::Archived));
        self::assertSame($template, $template->setFile($this->createStub(MediaInterface::class)));
        self::assertSame($template, $template->setFlattenOnGenerate(true));
        self::assertSame($template, $template->setRequiresSignature(true));
    }
}
