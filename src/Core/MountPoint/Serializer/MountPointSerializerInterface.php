<?php

declare(strict_types=1);

namespace Aurora\Core\MountPoint\Serializer;

use Aurora\Core\MountPoint\Entity\MountPointInterface;

interface MountPointSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MountPointInterface $mountPoint): array;
}
