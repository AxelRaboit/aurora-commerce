<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Serializer;

use Aurora\Core\Media\Library\Entity\MediaFolderInterface;

interface MediaFolderSerializerInterface
{
    /** @param array<int, int> $mediaCounts map of folder_id => count */
    public function withMediaCounts(array $mediaCounts): static;

    /** @return array<string, mixed> */
    public function serialize(MediaFolderInterface $folder): array;
}
