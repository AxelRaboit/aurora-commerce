<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;

interface DocumentInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getStatus(): DocumentStatusEnum;

    public function setStatus(DocumentStatusEnum $status): static;

    public function getCategory(): ?DocumentCategoryInterface;

    public function setCategory(?DocumentCategoryInterface $category): static;

    public function getFile(): ?MediaInterface;

    public function setFile(?MediaInterface $file): static;
}
