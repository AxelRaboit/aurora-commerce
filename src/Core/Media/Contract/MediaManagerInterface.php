<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Contract;

use Aurora\Core\Media\DTO\MediaFolderInput;
use Aurora\Core\Media\DTO\MediaInput;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Entity\MediaFolder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface MediaManagerInterface
{
    public function upload(UploadedFile $file, ?MediaFolder $folder = null): Media;

    public function update(Media $media, MediaInput $input): void;

    public function move(Media $media, ?MediaFolder $folder): void;

    public function delete(Media $media): void;

    public function createFolder(MediaFolderInput $input): MediaFolder;

    public function updateFolder(MediaFolder $folder, MediaFolderInput $input): void;

    public function deleteFolder(MediaFolder $folder): void;

    /** @param list<int> $orderedIds */
    public function reorder(array $orderedIds): void;

    /** @param list<int> $ids */
    public function bulkDelete(array $ids): void;

    /** @param list<int> $ids */
    public function bulkMove(array $ids, ?MediaFolder $folder): void;

    public function crop(Media $media, int $x, int $y, int $width, int $height): void;
}
