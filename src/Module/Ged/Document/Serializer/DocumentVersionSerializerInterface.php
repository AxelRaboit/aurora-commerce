<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Serializer;

use Aurora\Module\Ged\Document\Entity\DocumentVersionInterface;

interface DocumentVersionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(DocumentVersionInterface $version): array;
}
