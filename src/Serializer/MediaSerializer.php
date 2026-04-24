<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Media;

final readonly class MediaSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(Media $media): array
    {
        return [
            'id' => $media->getId(),
            'url' => $media->getPublicUrl(),
            'filename' => $media->getFilename(),
            'originalName' => $media->getOriginalName(),
            'mimeType' => $media->getMimeType(),
            'size' => $media->getSize(),
            'width' => $media->getWidth(),
            'height' => $media->getHeight(),
            'alt' => $media->getAlt(),
            'caption' => $media->getCaption(),
            'focalX' => $media->getFocalX(),
            'focalY' => $media->getFocalY(),
            'folderId' => $media->getFolder()?->getId(),
            'isImage' => $media->isImage(),
        ];
    }
}
