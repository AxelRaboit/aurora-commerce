<?php

declare(strict_types=1);

namespace Aurora\Core\MountPoint\Dto;

interface MountPointInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MountPointInputInterface;
}
