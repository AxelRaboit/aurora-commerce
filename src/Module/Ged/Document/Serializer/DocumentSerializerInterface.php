<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Serializer;

use Aurora\Module\Ged\Document\Entity\DocumentInterface;

interface DocumentSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(DocumentInterface $document): array;
}
