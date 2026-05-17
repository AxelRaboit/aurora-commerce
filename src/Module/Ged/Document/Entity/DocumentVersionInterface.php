<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use DateTimeImmutable;

interface DocumentVersionInterface
{
    public function getId(): ?int;

    public function getDocument(): DocumentInterface;

    public function setDocument(DocumentInterface $document): static;

    public function getFile(): MediaInterface;

    public function setFile(MediaInterface $file): static;

    public function getVersionNumber(): int;

    public function setVersionNumber(int $versionNumber): static;

    public function getCreatedAt(): DateTimeImmutable;

    public function getNote(): ?string;

    public function setNote(?string $note): static;
}
