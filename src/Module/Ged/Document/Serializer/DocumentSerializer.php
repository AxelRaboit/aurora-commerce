<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Serializer;

use Aurora\Module\Ged\Document\Entity\Document;
use DateTimeInterface;

final readonly class DocumentSerializer
{
    public function serialize(Document $document): array
    {
        $file = $document->getFile();
        $category = $document->getCategory();

        return [
            'id' => $document->getId(),
            'reference' => $document->getReference(),
            'title' => $document->getTitle(),
            'description' => $document->getDescription(),
            'status' => $document->getStatus()->value,
            'statusLabel' => $document->getStatus()->label(),
            'categoryId' => $category?->getId(),
            'categoryName' => $category?->getName(),
            'fileId' => $file?->getId(),
            'fileName' => $file?->getFileName(),
            'fileUrl' => $file?->getPublicUrl(),
            'fileMime' => $file?->getMimeType(),
            'fileSize' => $file?->getSize(),
            'createdAt' => $document->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $document->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
