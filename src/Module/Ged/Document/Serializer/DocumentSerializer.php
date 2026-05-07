<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Serializer;

use Aurora\Module\Ged\Document\Entity\Document;
use DateTimeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DocumentSerializer
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

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
            'statusLabel' => $this->translator->trans($document->getStatus()->getLabelKey()),
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
