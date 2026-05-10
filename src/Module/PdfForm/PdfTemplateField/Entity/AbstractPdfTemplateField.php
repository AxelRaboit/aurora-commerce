<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Entity;

use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractPdfTemplateField implements PdfTemplateFieldInterface
{
    #[ORM\ManyToOne(targetEntity: PdfTemplateInterface::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PdfTemplateInterface $template;

    #[ORM\Column(length: 200)]
    protected string $pdfFieldName;

    #[ORM\Column(length: 200)]
    protected string $label;

    #[ORM\Column(length: 20, enumType: PdfFieldTypeEnum::class)]
    protected PdfFieldTypeEnum $fieldType = PdfFieldTypeEnum::Text;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $mappingKey = null;

    #[ORM\Column(length: 500, nullable: true)]
    protected ?string $defaultValue = null;

    #[ORM\Column]
    protected int $position = 0;

    public function getTemplate(): PdfTemplateInterface
    {
        return $this->template;
    }

    public function setTemplate(PdfTemplateInterface $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getPdfFieldName(): string
    {
        return $this->pdfFieldName;
    }

    public function setPdfFieldName(string $pdfFieldName): static
    {
        $this->pdfFieldName = $pdfFieldName;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getFieldType(): PdfFieldTypeEnum
    {
        return $this->fieldType;
    }

    public function setFieldType(PdfFieldTypeEnum $fieldType): static
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    public function getMappingKey(): ?string
    {
        return $this->mappingKey;
    }

    public function setMappingKey(?string $mappingKey): static
    {
        $this->mappingKey = $mappingKey;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }
}
