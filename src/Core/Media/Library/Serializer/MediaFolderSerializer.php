<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Serializer;

use Aurora\Core\Media\Library\Entity\MediaFolderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MediaFolderSerializerInterface::class)]
class MediaFolderSerializer implements MediaFolderSerializerInterface
{
    /** @var array<int, int> */
    private array $mediaCounts = [];

    /**
     * @param array<int, int> $mediaCounts map of folder_id => count
     */
    public function withMediaCounts(array $mediaCounts): static
    {
        $clone = clone $this;
        $clone->mediaCounts = $mediaCounts;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(MediaFolderInterface $folder): array
    {
        return [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'parentId' => $folder->getParent()?->getId(),
            'position' => $folder->getPosition(),
            'mediaCount' => $this->mediaCounts[$folder->getId()] ?? 0,
        ];
    }
}
