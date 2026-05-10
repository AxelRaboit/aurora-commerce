<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\PdfForm\Enum\PdfDocumentStatusEnum;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPdfDocument implements PdfDocumentInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: PdfTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?PdfTemplateInterface $template = null;

    #[ORM\Column(length: 20, enumType: PdfDocumentStatusEnum::class)]
    protected PdfDocumentStatusEnum $status = PdfDocumentStatusEnum::Draft;

    #[ORM\Column(length: 300, nullable: true)]
    protected ?string $label = null;

    /** @var array<string, string> */
    #[ORM\Column(type: Types::JSON)]
    protected array $fieldValues = [];

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $contextType = null;

    #[ORM\Column(nullable: true)]
    protected ?int $contextId = null;

    /** Relative path within var/pdfform/ (e.g. 2026-05/PDF-000001.pdf). */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $filePath = null;

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTemplate(): ?PdfTemplateInterface
    {
        return $this->template;
    }

    public function setTemplate(?PdfTemplateInterface $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getStatus(): PdfDocumentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PdfDocumentStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getFieldValues(): array
    {
        return $this->fieldValues;
    }

    public function setFieldValues(array $fieldValues): static
    {
        $this->fieldValues = $fieldValues;

        return $this;
    }

    public function getContextType(): ?string
    {
        return $this->contextType;
    }

    public function setContextType(?string $contextType): static
    {
        $this->contextType = $contextType;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setContextId(?int $contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }
}
