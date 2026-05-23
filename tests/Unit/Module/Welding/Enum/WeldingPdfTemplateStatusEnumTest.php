<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Enum;

use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use PHPUnit\Framework\TestCase;

final class WeldingPdfTemplateStatusEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.welding.pdf_templates.status_draft', WeldingPdfTemplateStatusEnum::Draft->getLabelKey());
        self::assertSame('backend.welding.pdf_templates.status_active', WeldingPdfTemplateStatusEnum::Active->getLabelKey());
        self::assertSame('backend.welding.pdf_templates.status_archived', WeldingPdfTemplateStatusEnum::Archived->getLabelKey());
    }
}
