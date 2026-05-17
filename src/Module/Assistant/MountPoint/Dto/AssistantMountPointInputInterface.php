<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Dto;

use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;

interface AssistantMountPointInputInterface
{
    public function getName(): string;

    public function getPath(): string;

    public function getAccess(): MountPointAccessEnum;

    public function isActive(): bool;
}
