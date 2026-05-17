<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\MountPoint\Manager;

use Aurora\Module\Dev\MountPoint\Dto\MountPointInputInterface;
use Aurora\Module\Dev\MountPoint\Entity\MountPointInterface;

interface MountPointManagerInterface
{
    public function create(MountPointInputInterface $input): MountPointInterface;

    public function update(MountPointInterface $mountPoint, MountPointInputInterface $input): void;

    public function delete(MountPointInterface $mountPoint): void;
}
