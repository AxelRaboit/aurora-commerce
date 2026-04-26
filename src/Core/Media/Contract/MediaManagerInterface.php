<?php

declare(strict_types=1);

namespace App\Core\Media\Contract;

use App\Core\Media\DTO\MediaFolderInput;
use App\Core\Media\DTO\MediaInput;
use App\Core\Media\Entity\Media;
use App\Core\Media\Entity\MediaFolder;
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
}
