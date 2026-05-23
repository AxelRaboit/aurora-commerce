<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Entity;

use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;

interface WeldingPdfTemplateFieldInterface
{
    public function getId(): ?int;

    public function getTemplate(): WeldingPdfTemplateInterface;

    public function setTemplate(WeldingPdfTemplateInterface $template): static;

    public function getPdfFieldName(): string;

    public function setPdfFieldName(string $pdfFieldName): static;

    public function getLabel(): string;

    public function setLabel(string $label): static;

    public function getFieldType(): WeldingPdfFieldTypeEnum;

    public function setFieldType(WeldingPdfFieldTypeEnum $fieldType): static;

    public function getMappingKey(): ?string;

    public function setMappingKey(?string $mappingKey): static;

    public function getDefaultValue(): ?string;

    public function setDefaultValue(?string $defaultValue): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;
}
