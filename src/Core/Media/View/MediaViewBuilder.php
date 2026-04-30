<?php

declare(strict_types=1);

namespace Aurora\Core\Media\View;

use Aurora\Core\Media\Entity\MediaFolder;
use Aurora\Core\Media\Repository\MediaFolderRepository;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Media\Serializer\MediaFolderSerializer;
use Aurora\Core\Media\Serializer\MediaSerializer;

/**
 * Builds the Twig payload for the admin media library page. Centralises the
 * folder + media serialisation, search and storage stats so the controller
 * stays focused on flow (request parsing, redirects).
 */
final readonly class MediaViewBuilder
{
    public function __construct(
        private MediaRepository $mediaRepository,
        private MediaFolderRepository $folderRepository,
        private MediaSerializer $mediaSerializer,
        private MediaFolderSerializer $folderSerializer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(?int $folderId, string $search): array
    {
        $folderSerializer = $this->folderSerializer->withMediaCounts($this->mediaRepository->countGroupedByFolders());

        $folders = array_map(
            $folderSerializer->serialize(...),
            $this->folderRepository->findAllOrdered(),
        );

        $folder = null !== $folderId ? $this->folderRepository->find($folderId) : null;
        $folder = $folder instanceof MediaFolder ? $folder : null;

        $media = array_map(
            $this->mediaSerializer->serialize(...),
            $this->mediaRepository->findByFolder($folder, '' !== $search ? $search : null),
        );

        return [
            'folders' => $folders,
            'media' => $media,
            'currentFolderId' => $folderId,
            'search' => $search,
            'totalStorageBytes' => $this->mediaRepository->getTotalStorageSize(),
        ];
    }
}
