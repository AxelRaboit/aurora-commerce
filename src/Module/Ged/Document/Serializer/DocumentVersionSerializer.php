<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Serializer;

use Aurora\Core\Storage\Service\UploadUrlGenerator;
use Aurora\Module\Ged\Document\Entity\DocumentVersionInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentVersionSerializerInterface::class)]
class DocumentVersionSerializer implements DocumentVersionSerializerInterface
{
    public function __construct(
        protected readonly UploadUrlGenerator $uploadUrlGenerator,
    ) {}

    public function serialize(DocumentVersionInterface $version): array
    {
        return [
            'id' => $version->getId(),
            'versionNumber' => $version->getVersionNumber(),
            'fileName' => $version->getFileName(),
            'fileUrl' => $this->uploadUrlGenerator->publicUrl($version->getFilePath()),
            'fileMime' => $version->getMimeType(),
            'fileSize' => $version->getSize(),
            'note' => $version->getNote(),
            'createdAt' => $version->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
