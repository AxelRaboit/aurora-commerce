<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Serializer;

use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;

interface DocumentFolderSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(DocumentFolderInterface $folder): array;
}
