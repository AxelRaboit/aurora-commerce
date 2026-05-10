<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Dto;

use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;

interface PdfTemplateFieldInputInterface
{
    public function getPdfFieldName(): string;

    public function getLabel(): string;

    public function getFieldType(): PdfFieldTypeEnum;

    public function getMappingKey(): ?string;

    public function getDefaultValue(): ?string;

    public function getPosition(): int;
}
