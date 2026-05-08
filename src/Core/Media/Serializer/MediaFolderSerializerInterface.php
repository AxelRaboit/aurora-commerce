<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Serializer;

use Aurora\Core\Media\Entity\MediaFolderInterface;

interface MediaFolderSerializerInterface
{
    /** @param array<int, int> $mediaCounts map of folder_id => count */
    public function withMediaCounts(array $mediaCounts): static;

    /** @return array<string, mixed> */
    public function serialize(MediaFolderInterface $folder): array;
}
