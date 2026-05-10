<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Entity;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractDocument implements DocumentInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 200)]
    protected string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 20, enumType: DocumentStatusEnum::class)]
    protected DocumentStatusEnum $status = DocumentStatusEnum::Draft;

    #[ORM\ManyToOne(targetEntity: DocumentCategoryInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?DocumentCategoryInterface $category = null;

    /** Physical file stored via Core/Media. */
    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaInterface $file = null;

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getStatus(): DocumentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(DocumentStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCategory(): ?DocumentCategoryInterface
    {
        return $this->category;
    }

    public function setCategory(?DocumentCategoryInterface $category): static
    {
        $this->category = $category;

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
}
