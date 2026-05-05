<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Serializer;

use Aurora\Core\Media\Entity\Media;
use DateTimeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class MediaSerializer
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

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
            'url' => $media->getPublicUrl().'?v='.($media->getUpdatedAt()?->getTimestamp() ?? 0),
            'permalink' => $this->urlGenerator->generate('media_view', ['id' => $media->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
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
            'folderName' => $media->getFolder()?->getName(),
            'isImage' => $media->isImage(),
            'isVideo' => $media->isVideo(),
            'variants' => $variantUrls,
            'thumbnailUrl' => $variantUrls['thumbnail'] ?? $media->getPublicUrl(),
            'position' => $media->getPosition(),
            'createdAt' => $media->getCreatedAt()?->format(DateTimeInterface::ATOM),
            'updatedAt' => $media->getUpdatedAt()?->format(DateTimeInterface::ATOM),
            'uploadedBy' => $media->getUploadedBy()?->getName(),
        ];
    }
}
