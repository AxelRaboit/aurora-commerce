<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWeldingPdfTemplate implements WeldingPdfTemplateInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 200)]
    protected string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 20, enumType: WeldingPdfTemplateStatusEnum::class)]
    protected WeldingPdfTemplateStatusEnum $status = WeldingPdfTemplateStatusEnum::Draft;

    #[ORM\Column(options: ['default' => false])]
    protected bool $flattenOnGenerate = false;

    #[ORM\Column(options: ['default' => false])]
    protected bool $requiresSignature = false;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaInterface $file = null;

    /** @var Collection<int, WeldingPdfTemplateFieldInterface> */
    #[ORM\OneToMany(targetEntity: WeldingPdfTemplateFieldInterface::class, mappedBy: 'template', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $fields;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): WeldingPdfTemplateStatusEnum
    {
        return $this->status;
    }

    public function setStatus(WeldingPdfTemplateStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getFile(): ?MediaInterface
    {
        return $this->file;
    }

    public function setFile(?MediaInterface $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function isFlattenOnGenerate(): bool
    {
        return $this->flattenOnGenerate;
    }

    public function setFlattenOnGenerate(bool $flatten): static
    {
        $this->flattenOnGenerate = $flatten;

        return $this;
    }

    public function isRequiresSignature(): bool
    {
        return $this->requiresSignature;
    }

    public function setRequiresSignature(bool $requiresSignature): static
    {
        $this->requiresSignature = $requiresSignature;

        return $this;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }
}
