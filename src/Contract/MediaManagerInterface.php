<?php

declare(strict_types=1);

namespace App\Contract;

use App\DTO\MediaFolderInput;
use App\DTO\MediaInput;
use App\Entity\Media;
use App\Entity\MediaFolder;
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
