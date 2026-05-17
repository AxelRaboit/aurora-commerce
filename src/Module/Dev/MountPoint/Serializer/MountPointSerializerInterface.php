<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\MountPoint\Serializer;

use Aurora\Module\Dev\MountPoint\Entity\MountPointInterface;

interface MountPointSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MountPointInterface $mountPoint): array;
}
