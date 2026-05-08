<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Serializer;

use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentCategorySerializerInterface::class)]
class DocumentCategorySerializer implements DocumentCategorySerializerInterface
{
    public function serialize(DocumentCategoryInterface $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
            'createdAt' => $category->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $category->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
