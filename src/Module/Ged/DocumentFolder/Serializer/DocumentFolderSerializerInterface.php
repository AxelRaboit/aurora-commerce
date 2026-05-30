<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Serializer;

use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;

interface DocumentFolderSerializerInterface
{
    /** @param array<int, int> $documentCounts map of folder_id => count */
    public function withDocumentCounts(array $documentCounts): static;

    /** @return array<string, mixed> */
    public function serialize(DocumentFolderInterface $folder): array;
}
