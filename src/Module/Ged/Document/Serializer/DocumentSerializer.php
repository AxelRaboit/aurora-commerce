<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Serializer;

use Aurora\Core\Storage\Service\UploadUrlGenerator;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(DocumentSerializerInterface::class)]
class DocumentSerializer implements DocumentSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly UploadUrlGenerator $uploadUrlGenerator,
    ) {}

    public function serialize(DocumentInterface $document): array
    {
        $category = $document->getCategory();
        $folder = $document->getFolder();

        return [
            'id' => $document->getId(),
            'reference' => $document->getReference(),
            'title' => $document->getTitle(),
            'description' => $document->getDescription(),
            'status' => $document->getStatus()->value,
            'statusLabel' => $this->translator->trans($document->getStatus()->getLabelKey()),
            'categoryId' => $category?->getId(),
            'categoryName' => $category?->getName(),
            // Self-owned file fields — no Media coupling. URL is built via
            // the canonical `uploads_serve` route through UploadUrlGenerator
            // (no hardcoded `/uploads/` prefix).
            'filePath' => $document->getFilePath(),
            'fileName' => $document->getFileName(),
            'originalName' => $document->getOriginalName(),
            'fileUrl' => $this->uploadUrlGenerator->publicUrl($document->getFilePath()),
            'fileMime' => $document->getMimeType(),
            'fileSize' => $document->getSize(),
            'tagIds' => $document->getTags()->map(static fn ($tag): ?int => $tag->getId())->toArray(),
            'tags' => $document->getTags()->map(static fn ($tag): array => ['id' => $tag->getId(), 'name' => $tag->getName(), 'color' => $tag->getColor()])->toArray(),
            'folderId' => $folder?->getId(),
            'folderName' => $folder?->getName(),
            'createdAt' => $document->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $document->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
