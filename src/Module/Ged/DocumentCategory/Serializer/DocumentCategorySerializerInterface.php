<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Serializer;

use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;

interface DocumentCategorySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(DocumentCategoryInterface $category): array;
}
