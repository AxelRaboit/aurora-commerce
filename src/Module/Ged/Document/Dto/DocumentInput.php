<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Dto;

use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

class DocumentInput implements DocumentInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.ged.documents.errors.title_required')]
        #[Assert\Length(max: 200)]
        public readonly string $title = '',
        public readonly ?string $description = null,
        public readonly DocumentStatusEnum $status = DocumentStatusEnum::Draft,
        public readonly ?int $categoryId = null,
        // File metadata — populated by the upload endpoint, then carried
        // through the form submit. The actual bytes already live on disk
        // under `var/uploads/<filePath>` by the time the form is submitted.
        public readonly ?string $filePath = null,
        public readonly ?string $fileName = null,
        public readonly ?string $originalName = null,
        public readonly ?string $mimeType = null,
        public readonly ?int $size = null,
        public readonly array $tagIds = [],
        public readonly ?int $folderId = null,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): DocumentStatusEnum
    {
        return $this->status;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getTagIds(): array
    {
        return $this->tagIds;
    }

    public function getFolderId(): ?int
    {
        return $this->folderId;
    }
}
