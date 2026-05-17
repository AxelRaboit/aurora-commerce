<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Serializer;

use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;

interface AssistantMountPointSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AssistantMountPointInterface $mountPoint): array;
}
