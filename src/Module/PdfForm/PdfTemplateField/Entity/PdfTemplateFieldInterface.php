<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Entity;

use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;

interface PdfTemplateFieldInterface
{
    public function getId(): ?int;

    public function getTemplate(): PdfTemplateInterface;

    public function setTemplate(PdfTemplateInterface $template): static;

    public function getPdfFieldName(): string;

    public function setPdfFieldName(string $pdfFieldName): static;

    public function getLabel(): string;

    public function setLabel(string $label): static;

    public function getFieldType(): PdfFieldTypeEnum;

    public function setFieldType(PdfFieldTypeEnum $fieldType): static;

    public function getMappingKey(): ?string;

    public function setMappingKey(?string $mappingKey): static;

    public function getDefaultValue(): ?string;

    public function setDefaultValue(?string $defaultValue): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;
}
