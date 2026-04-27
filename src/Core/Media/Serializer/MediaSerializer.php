<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Serializer;

use Aurora\Core\Media\Entity\Media;
use DateTimeInterface;

final readonly class MediaSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(Media $media): array
    {
        $variantUrls = [];
        foreach (array_keys($media->getVariants()) as $name) {
            $variantUrls[$name] = $media->getVariantUrl($name);
        }

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
            'focalPositionCss' => $media->getFocalPositionCss(),
            'folderId' => $media->getFolder()?->getId(),
            'isImage' => $media->isImage(),
            'variants' => $variantUrls,
            'thumbnailUrl' => $variantUrls['thumbnail'] ?? $media->getPublicUrl(),
            'position' => $media->getPosition(),
            'createdAt' => $media->getCreatedAt()?->format(DateTimeInterface::ATOM),
            'updatedAt' => $media->getUpdatedAt()?->format(DateTimeInterface::ATOM),
            'uploadedBy' => $media->getUploadedBy()?->getName(),
        ];
    }
}
