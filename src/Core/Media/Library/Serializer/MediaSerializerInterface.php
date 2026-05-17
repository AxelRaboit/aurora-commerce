<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Serializer;

use Aurora\Core\Media\Library\Entity\MediaInterface;

interface MediaSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MediaInterface $media): array;
}
