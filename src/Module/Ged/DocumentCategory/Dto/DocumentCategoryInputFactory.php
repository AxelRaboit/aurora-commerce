<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentCategoryInputFactoryInterface::class)]
class DocumentCategoryInputFactory implements DocumentCategoryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): DocumentCategoryInputInterface
    {
        return new DocumentCategoryInput(
            name: Str::trimFromArray($data, 'name'),
            description: Str::trimOrNullFromArray($data, 'description'),
        );
    }
}
