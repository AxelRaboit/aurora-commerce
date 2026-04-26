<?php

declare(strict_types=1);

namespace App\Core\Media\Serializer;

use App\Core\Media\Entity\MediaFolder;

final readonly class MediaFolderSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(MediaFolder $folder): array
    {
        return [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'parentId' => $folder->getParent()?->getId(),
            'position' => $folder->getPosition(),
        ];
    }
}
