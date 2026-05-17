<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\MountPoint\Dto;

interface MountPointInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MountPointInputInterface;
}
