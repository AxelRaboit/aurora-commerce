<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Welding\Enum\WeldingPdfDocumentStatusEnum;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWeldingPdfDocument implements WeldingPdfDocumentInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: WeldingPdfTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?WeldingPdfTemplateInterface $template = null;

    #[ORM\Column(length: 20, enumType: WeldingPdfDocumentStatusEnum::class)]
    protected WeldingPdfDocumentStatusEnum $status = WeldingPdfDocumentStatusEnum::Draft;

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

    public function getTemplate(): ?WeldingPdfTemplateInterface
    {
        return $this->template;
    }

    public function setTemplate(?WeldingPdfTemplateInterface $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getStatus(): WeldingPdfDocumentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(WeldingPdfDocumentStatusEnum $status): static
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
