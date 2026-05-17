<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Serializer;

use Aurora\Module\Media\Library\Entity\MediaInterface;

interface MediaSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MediaInterface $media): array;
}
