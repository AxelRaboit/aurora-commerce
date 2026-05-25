<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Entity;

use DateTimeImmutable;

interface MediaVersionInterface
{
    public function getId(): ?int;

    public function getMedia(): MediaInterface;

    public function setMedia(MediaInterface $media): static;

    public function getPath(): string;

    public function setPath(string $path): static;

    public function getFilename(): string;

    public function setFilename(string $filename): static;

    public function getOriginalName(): string;

    public function setOriginalName(string $originalName): static;

    public function getMimeType(): string;

    public function setMimeType(string $mimeType): static;

    public function getSize(): int;

    public function setSize(int $size): static;

    public function getWidth(): ?int;

    public function setWidth(?int $width): static;

    public function getHeight(): ?int;

    public function setHeight(?int $height): static;

    public function getVersionNumber(): int;

    public function setVersionNumber(int $versionNumber): static;

    public function getCreatedAt(): DateTimeImmutable;

    public function getNote(): ?string;

    public function setNote(?string $note): static;
}
