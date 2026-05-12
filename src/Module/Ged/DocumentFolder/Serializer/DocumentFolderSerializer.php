<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Serializer;

use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentFolderSerializerInterface::class)]
class DocumentFolderSerializer implements DocumentFolderSerializerInterface
{
    public function serialize(DocumentFolderInterface $folder): array
    {
        return [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'parentId' => $folder->getParent()?->getId(),
            'position' => $folder->getPosition(),
        ];
    }
}
