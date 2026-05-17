<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Serializer;

use Aurora\Module\Media\Library\Service\MediaUrlGenerator;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(DocumentSerializerInterface::class)]
class DocumentSerializer implements DocumentSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    public function serialize(DocumentInterface $document): array
    {
        $file = $document->getFile();
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
            'fileId' => $file?->getId(),
            'fileName' => $file?->getFileName(),
            'fileUrl' => $this->mediaUrlGenerator->publicUrl($file),
            'fileMime' => $file?->getMimeType(),
            'fileSize' => $file?->getSize(),
            'tagIds' => $document->getTags()->map(static fn ($tag): ?int => $tag->getId())->toArray(),
            'tags' => $document->getTags()->map(static fn ($tag): array => ['id' => $tag->getId(), 'name' => $tag->getName(), 'color' => $tag->getColor()])->toArray(),
            'folderId' => $folder?->getId(),
            'folderName' => $folder?->getName(),
            'createdAt' => $document->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $document->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
