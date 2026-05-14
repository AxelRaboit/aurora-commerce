<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Entity;

use Aurora\Core\User\Entity\User;
use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMedia implements MediaInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 255)]
    protected string $filename;

    #[ORM\Column(length: 255)]
    protected string $originalName;

    #[ORM\Column(length: 100)]
    protected string $mimeType;

    #[ORM\Column]
    protected int $size;

    #[ORM\Column(length: 255)]
    protected string $path;

    #[ORM\Column(nullable: true)]
    protected ?int $width = null;

    #[ORM\Column(nullable: true)]
    protected ?int $height = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $alt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $caption = null;

    #[ORM\Column(type: 'float', nullable: true)]
    protected ?float $focalX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    protected ?float $focalY = null;

    #[ORM\ManyToOne(targetEntity: MediaFolderInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaFolderInterface $folder = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?User $uploadedBy = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    /** @var array<string, string> variant name (thumbnail/medium/large) → relative path under uploads */
    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    protected array $variants = [];

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    public function getFocalX(): ?float
    {
        return $this->focalX;
    }

    public function setFocalX(?float $focalX): static
    {
        $this->focalX = $focalX;

        return $this;
    }

    public function getFocalY(): ?float
    {
        return $this->focalY;
    }

    public function setFocalY(?float $focalY): static
    {
        $this->focalY = $focalY;

        return $this;
    }

    public function getFolder(): ?MediaFolderInterface
    {
        return $this->folder;
    }

    public function setFolder(?MediaFolderInterface $folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function setVariants(array $variants): static
    {
        $this->variants = $variants;

        return $this;
    }

    public function getVariantUrl(string $size): ?string
    {
        $path = $this->variants[$size] ?? null;

        return null === $path ? null : '/uploads/'.$path;
    }

    /**
     * Returns a CSS object-position value like "50% 25%" based on the focal
     * point, or "50% 50%" (centered) when no focal point is set.
     */
    public function getFocalPositionCss(): string
    {
        $x = null !== $this->focalX ? round($this->focalX * 100, 2) : 50;
        $y = null !== $this->focalY ? round($this->focalY * 100, 2) : 50;

        return sprintf('%s%% %s%%', $x, $y);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mimeType, 'video/');
    }

    public function getPublicUrl(): string
    {
        return '/uploads/'.$this->path;
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

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $user): static
    {
        $this->uploadedBy = $user;

        return $this;
    }
}
