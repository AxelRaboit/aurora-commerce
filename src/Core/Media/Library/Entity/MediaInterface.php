<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\User\Entity\User;

interface MediaInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getFilename(): string;

    public function setFilename(string $filename): static;

    public function getOriginalName(): string;

    public function setOriginalName(string $originalName): static;

    public function getMimeType(): string;

    public function setMimeType(string $mimeType): static;

    public function getSize(): int;

    public function setSize(int $size): static;

    public function getPath(): string;

    public function setPath(string $path): static;

    public function getWidth(): ?int;

    public function setWidth(?int $width): static;

    public function getHeight(): ?int;

    public function setHeight(?int $height): static;

    public function getAlt(): ?string;

    public function setAlt(?string $alt): static;

    public function getCaption(): ?string;

    public function setCaption(?string $caption): static;

    public function getFocalX(): ?float;

    public function setFocalX(?float $focalX): static;

    public function getFocalY(): ?float;

    public function setFocalY(?float $focalY): static;

    public function getFolder(): ?MediaFolderInterface;

    public function setFolder(?MediaFolderInterface $folder): static;

    /** @return array<string, string> */
    public function getVariants(): array;

    /** @param array<string, string> $variants */
    public function setVariants(array $variants): static;

    /** Relative path of the named variant (no leading slash), or null. */
    public function getVariantPath(string $size): ?string;

    public function getFocalPositionCss(): string;

    public function isImage(): bool;

    public function isVideo(): bool;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getUploadedBy(): ?User;

    public function setUploadedBy(?User $user): static;
}
