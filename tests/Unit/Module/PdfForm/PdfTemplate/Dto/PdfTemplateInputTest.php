<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\PdfTemplate\Dto;

use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use Aurora\Module\PdfForm\PdfTemplate\Dto\PdfTemplateInput;
use PHPUnit\Framework\TestCase;

final class PdfTemplateInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new PdfTemplateInput();

        self::assertSame('', $input->getName());
        self::assertNull($input->getDescription());
        self::assertSame(PdfTemplateStatusEnum::Draft, $input->getStatus());
        self::assertNull($input->getFileId());
        self::assertFalse($input->isFlattenOnGenerate());
        self::assertFalse($input->isRequiresSignature());
    }

    public function testConstructorValues(): void
    {
        $input = new PdfTemplateInput(
            name: 'Contract',
            description: 'Standard',
            status: PdfTemplateStatusEnum::Active,
            fileId: 7,
            flattenOnGenerate: true,
            requiresSignature: true,
        );

        self::assertSame('Contract', $input->getName());
        self::assertSame('Standard', $input->getDescription());
        self::assertSame(PdfTemplateStatusEnum::Active, $input->getStatus());
        self::assertSame(7, $input->getFileId());
        self::assertTrue($input->isFlattenOnGenerate());
        self::assertTrue($input->isRequiresSignature());
    }
}
