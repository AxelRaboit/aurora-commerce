<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Serializer;

use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentFolderSerializerInterface::class)]
class DocumentFolderSerializer implements DocumentFolderSerializerInterface
{
    /** @var array<int, int> */
    private array $documentCounts = [];

    /**
     * @param array<int, int> $documentCounts map of folder_id => count
     */
    public function withDocumentCounts(array $documentCounts): static
    {
        $clone = clone $this;
        $clone->documentCounts = $documentCounts;

        return $clone;
    }

    public function serialize(DocumentFolderInterface $folder): array
    {
        return [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'parentId' => $folder->getParent()?->getId(),
            'position' => $folder->getPosition(),
            'documentCount' => $this->documentCounts[$folder->getId()] ?? 0,
        ];
    }
}
