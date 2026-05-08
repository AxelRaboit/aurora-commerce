<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Manager;

use Aurora\Core\Media\Dto\MediaFolderInputInterface;
use Aurora\Core\Media\Dto\MediaInputInterface;
use Aurora\Core\Media\Entity\MediaFolderInterface;
use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Media\Enum\StorageAreaEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface MediaManagerInterface
{
    public function upload(UploadedFile $file, ?MediaFolderInterface $folder = null, StorageAreaEnum $area = StorageAreaEnum::Media): MediaInterface;

    public function update(MediaInterface $media, MediaInputInterface $input): void;

    public function move(MediaInterface $media, ?MediaFolderInterface $folder): void;

    public function delete(MediaInterface $media): void;

    public function createFolder(MediaFolderInputInterface $input): MediaFolderInterface;

    public function updateFolder(MediaFolderInterface $folder, MediaFolderInputInterface $input): void;

    public function deleteFolder(MediaFolderInterface $folder): void;

    /** @param list<int> $orderedIds */
    public function reorder(array $orderedIds): void;

    /** @param list<int> $ids */
    public function bulkDelete(array $ids): void;

    /** @param list<int> $ids */
    public function bulkMove(array $ids, ?MediaFolderInterface $folder): void;

    public function crop(MediaInterface $media, int $x, int $y, int $width, int $height): void;
}
