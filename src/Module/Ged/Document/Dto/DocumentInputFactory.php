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
            fileId: isset($data['fileId']) ? (int) $data['fileId'] : null,
            tagIds: $tagIds,
            folderId: isset($data['folderId']) ? (int) $data['folderId'] : null,
        );
    }
}
