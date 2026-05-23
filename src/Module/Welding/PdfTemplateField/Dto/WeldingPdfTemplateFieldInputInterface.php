<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Dto;

use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;

interface WeldingPdfTemplateFieldInputInterface
{
    public function getPdfFieldName(): string;

    public function getLabel(): string;

    public function getFieldType(): WeldingPdfFieldTypeEnum;

    public function getMappingKey(): ?string;

    public function getDefaultValue(): ?string;

    public function getPosition(): int;
}
