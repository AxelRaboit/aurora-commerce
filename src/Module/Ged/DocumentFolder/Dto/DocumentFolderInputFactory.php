<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentFolderInputFactoryInterface::class)]
class DocumentFolderInputFactory implements DocumentFolderInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): DocumentFolderInputInterface
    {
        return new DocumentFolderInput(
            name: Str::trimFromArray($data, 'name'),
            parentId: empty($data['parentId']) ? null : (int) $data['parentId'],
        );
    }
}
