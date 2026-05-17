<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\MountPoint\Dto;

use Aurora\Module\Dev\MountPoint\Enum\MountPointTypeEnum;

interface MountPointInputInterface
{
    public function getName(): string;

    public function getType(): MountPointTypeEnum;

    public function getHost(): string;

    public function getPort(): ?int;

    public function getUsername(): ?string;

    public function getPassword(): ?string;

    public function getDatabase(): ?string;

    public function getSshPublicKey(): ?string;

    public function getSshPrivateKey(): ?string;

    /** @return array<string, mixed> */
    public function getConfig(): array;
}
