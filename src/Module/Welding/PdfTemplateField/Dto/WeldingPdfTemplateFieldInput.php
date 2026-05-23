<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Dto;

use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class WeldingPdfTemplateFieldInput implements WeldingPdfTemplateFieldInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.welding.pdf_template_fields.errors.pdf_field_name_required')]
        #[Assert\Length(max: 200)]
        public readonly string $pdfFieldName = '',
        #[Assert\NotBlank(message: 'backend.welding.pdf_template_fields.errors.label_required')]
        #[Assert\Length(max: 200)]
        public readonly string $label = '',
        public readonly WeldingPdfFieldTypeEnum $fieldType = WeldingPdfFieldTypeEnum::Text,
        public readonly ?string $mappingKey = null,
        public readonly ?string $defaultValue = null,
        public readonly int $position = 0,
    ) {}

    public function getPdfFieldName(): string
    {
        return $this->pdfFieldName;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getFieldType(): WeldingPdfFieldTypeEnum
    {
        return $this->fieldType;
    }

    public function getMappingKey(): ?string
    {
        return $this->mappingKey;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
