<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Entity;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Doctrine\Common\Collections\Collection;

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

    /** @return Collection<int, DocumentTagInterface> */
    public function getTags(): Collection;

    public function addTag(DocumentTagInterface $tag): static;

    public function removeTag(DocumentTagInterface $tag): static;

    public function clearTags(): static;

    public function getFolder(): ?DocumentFolderInterface;

    public function setFolder(?DocumentFolderInterface $folder): static;
}
