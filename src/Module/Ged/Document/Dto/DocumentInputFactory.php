<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentInputFactoryInterface::class)]
class DocumentInputFactory implements DocumentInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): DocumentInputInterface
    {
        $tagIds = array_map(intval(...), (array) ($data['tagIds'] ?? []));

        return new DocumentInput(
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            status: DocumentStatusEnum::tryFrom($data['status'] ?? '') ?? DocumentStatusEnum::Draft,
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            filePath: Str::trimOrNullFromArray($data, 'filePath'),
            fileName: Str::trimOrNullFromArray($data, 'fileName'),
            originalName: Str::trimOrNullFromArray($data, 'originalName'),
            mimeType: Str::trimOrNullFromArray($data, 'mimeType'),
            size: isset($data['size']) ? (int) $data['size'] : null,
            tagIds: $tagIds,
            folderId: isset($data['folderId']) ? (int) $data['folderId'] : null,
        );
    }
}
