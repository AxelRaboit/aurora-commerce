<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Entity;

use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Table(name: 'media')]
class Media implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $filename;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 100)]
    private string $mimeType;

    #[ORM\Column]
    private int $size;

    #[ORM\Column(length: 255)]
    private string $path;

    #[ORM\Column(nullable: true)]
    private ?int $width = null;

    #[ORM\Column(nullable: true)]
    private ?int $height = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $focalX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $focalY = null;

    #[ORM\ManyToOne(targetEntity: MediaFolder::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MediaFolder $folder = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $uploadedBy = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /** @var array<string, string> variant name (thumbnail/medium/large) → relative path under uploads */
    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $variants = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFolder(): ?MediaFolder
    {
        return $this->folder;
    }

    public function setFolder(?MediaFolder $folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    /** @return array<string, string> */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /** @param array<string, string> $variants */
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
