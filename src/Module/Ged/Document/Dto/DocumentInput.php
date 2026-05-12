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
        public readonly ?int $fileId = null,
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

    public function getFileId(): ?int
    {
        return $this->fileId;
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
