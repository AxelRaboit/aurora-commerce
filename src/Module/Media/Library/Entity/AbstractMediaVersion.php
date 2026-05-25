<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractMediaVersion implements MediaVersionInterface
{
    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected MediaInterface $media;

    // ── File snapshot ────────────────────────────────────────────────────
    // Each version snapshots the file metadata of a media that was current
    // at some point. The bytes are NOT duplicated: every crop writes a new
    // `media/Y/m/<file>` path, so historical version rows keep pointing at
    // their own (untouched) source file. Variants are a current-only concern
    // and are never snapshotted here.

    #[ORM\Column(length: 255)]
    protected string $path;

    #[ORM\Column(length: 255)]
    protected string $filename;

    #[ORM\Column(length: 255)]
    protected string $originalName;

    #[ORM\Column(length: 100)]
    protected string $mimeType;

    #[ORM\Column(type: Types::INTEGER)]
    protected int $size;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $width = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $height = null;

    #[ORM\Column]
    protected int $versionNumber;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $note = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function setMedia(MediaInterface $media): static
    {
        $this->media = $media;

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

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(int $versionNumber): static
    {
        $this->versionNumber = $versionNumber;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
