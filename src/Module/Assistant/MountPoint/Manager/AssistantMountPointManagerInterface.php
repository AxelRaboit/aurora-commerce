<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Manager;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\MountPoint\Dto\AssistantMountPointInputInterface;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;

interface AssistantMountPointManagerInterface
{
    public function create(CoreUserInterface $user, AssistantMountPointInputInterface $input): AssistantMountPointInterface;

    public function update(AssistantMountPointInterface $mountPoint, AssistantMountPointInputInterface $input): void;

    public function delete(AssistantMountPointInterface $mountPoint): void;
}
