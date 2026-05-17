<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Dto;

use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Symfony\Component\Validator\Constraints as Assert;

class AssistantMountPointInput implements AssistantMountPointInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'assistant.mount_point.errors.name_required')]
        #[Assert\Length(max: 100)]
        public readonly string $name = '',
        #[Assert\NotBlank(message: 'assistant.mount_point.errors.path_required')]
        #[Assert\Length(max: 1024)]
        #[Assert\Regex(pattern: '/^\//', message: 'assistant.mount_point.errors.path_absolute')]
        public readonly string $path = '',
        public readonly MountPointAccessEnum $access = MountPointAccessEnum::ReadOnly,
        public readonly bool $active = true,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getAccess(): MountPointAccessEnum
    {
        return $this->access;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
