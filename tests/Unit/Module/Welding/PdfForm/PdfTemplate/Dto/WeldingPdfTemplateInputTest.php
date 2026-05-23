<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm\PdfTemplate\Dto;

use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Aurora\Module\Welding\PdfTemplate\Dto\WeldingPdfTemplateInput;
use PHPUnit\Framework\TestCase;

final class WeldingPdfTemplateInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new WeldingPdfTemplateInput();

        self::assertSame('', $input->getName());
        self::assertNull($input->getDescription());
        self::assertSame(WeldingPdfTemplateStatusEnum::Draft, $input->getStatus());
        self::assertNull($input->getFileId());
        self::assertFalse($input->isFlattenOnGenerate());
        self::assertFalse($input->isRequiresSignature());
    }

    public function testConstructorValues(): void
    {
        $input = new WeldingPdfTemplateInput(
            name: 'Contract',
            description: 'Standard',
            status: WeldingPdfTemplateStatusEnum::Active,
            fileId: 7,
            flattenOnGenerate: true,
            requiresSignature: true,
        );

        self::assertSame('Contract', $input->getName());
        self::assertSame('Standard', $input->getDescription());
        self::assertSame(WeldingPdfTemplateStatusEnum::Active, $input->getStatus());
        self::assertSame(7, $input->getFileId());
        self::assertTrue($input->isFlattenOnGenerate());
        self::assertTrue($input->isRequiresSignature());
    }
}
