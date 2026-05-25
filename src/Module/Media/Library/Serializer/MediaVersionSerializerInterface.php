<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Serializer;

use Aurora\Module\Media\Library\Entity\MediaVersionInterface;

interface MediaVersionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MediaVersionInterface $version): array;
}
