<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\MountPoint\Serializer;

use Aurora\Core\Dev\MountPoint\Entity\MountPointInterface;

interface MountPointSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MountPointInterface $mountPoint): array;
}
