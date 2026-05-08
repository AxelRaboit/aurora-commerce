<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Serializer;

use Aurora\Core\Media\Entity\MediaInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsAlias(MediaSerializerInterface::class)]
class MediaSerializer implements MediaSerializerInterface
{
    public function __construct(
        protected readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serialize(MediaInterface $media): array
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
