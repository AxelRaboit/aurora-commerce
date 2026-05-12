<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Serializer;

use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;

interface DocumentTagSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(DocumentTagInterface $tag): array;
}
