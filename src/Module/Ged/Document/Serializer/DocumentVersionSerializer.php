<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Serializer;

use Aurora\Module\Ged\Document\Entity\DocumentVersionInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentVersionSerializerInterface::class)]
class DocumentVersionSerializer implements DocumentVersionSerializerInterface
{
    public function serialize(DocumentVersionInterface $version): array
    {
        $file = $version->getFile();

        return [
            'id' => $version->getId(),
            'versionNumber' => $version->getVersionNumber(),
            'fileName' => $file->getFileName(),
            'fileUrl' => $file->getPublicUrl(),
            'fileMime' => $file->getMimeType(),
            'fileSize' => $file->getSize(),
            'note' => $version->getNote(),
            'createdAt' => $version->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
