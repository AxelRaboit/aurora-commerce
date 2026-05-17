<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Dto;

interface AssistantMountPointInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): AssistantMountPointInputInterface;
}
