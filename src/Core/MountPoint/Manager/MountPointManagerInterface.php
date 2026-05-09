<?php

declare(strict_types=1);

namespace Aurora\Core\MountPoint\Manager;

use Aurora\Core\MountPoint\Dto\MountPointInputInterface;
use Aurora\Core\MountPoint\Entity\MountPointInterface;

interface MountPointManagerInterface
{
    public function create(MountPointInputInterface $input): MountPointInterface;

    public function update(MountPointInterface $mountPoint, MountPointInputInterface $input): void;

    public function delete(MountPointInterface $mountPoint): void;
}
