<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;

interface AssistantMountPointInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getPath(): string;

    public function setPath(string $path): static;

    public function getAccess(): MountPointAccessEnum;

    public function setAccess(MountPointAccessEnum $access): static;

    public function isActive(): bool;

    public function setActive(bool $active): static;
}
