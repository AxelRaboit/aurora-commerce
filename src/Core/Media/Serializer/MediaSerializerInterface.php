<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Serializer;

use Aurora\Core\Media\Entity\MediaInterface;

interface MediaSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MediaInterface $media): array;
}
