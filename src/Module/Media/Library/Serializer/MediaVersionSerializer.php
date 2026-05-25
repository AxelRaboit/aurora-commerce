<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Serializer;

use Aurora\Core\Storage\Service\UploadUrlGenerator;
use Aurora\Module\Media\Library\Entity\MediaVersionInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MediaVersionSerializerInterface::class)]
class MediaVersionSerializer implements MediaVersionSerializerInterface
{
    public function __construct(
        protected readonly UploadUrlGenerator $uploadUrlGenerator,
    ) {}

    public function serialize(MediaVersionInterface $version): array
    {
        return [
            'id' => $version->getId(),
            'versionNumber' => $version->getVersionNumber(),
            'filename' => $version->getFilename(),
            'url' => $this->uploadUrlGenerator->publicUrl($version->getPath()),
            'mimeType' => $version->getMimeType(),
            'size' => $version->getSize(),
            'width' => $version->getWidth(),
            'height' => $version->getHeight(),
            'note' => $version->getNote(),
            'createdAt' => $version->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
