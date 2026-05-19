<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * A filesystem path the assistant is allowed to inspect on behalf of its
 * owning user. Stores the absolute path verbatim — the tool layer is in
 * charge of normalising it and enforcing the path-traversal guard. The
 * row itself is plain (no encryption) because path strings rarely carry
 * secrets and the user can list them in the UI.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractAssistantMountPoint implements AssistantMountPointInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    #[ORM\Column(length: 100)]
    protected string $name = '';

    #[ORM\Column(length: 1024)]
    protected string $path = '';

    #[ORM\Column(length: 20, enumType: MountPointAccessEnum::class)]
    protected MountPointAccessEnum $access = MountPointAccessEnum::ReadOnly;

    #[ORM\Column(options: ['default' => true])]
    protected bool $active = true;

    public function getUser(): CoreUserInterface
    {
        return $this->user;
    }

    public function setUser(CoreUserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getAccess(): MountPointAccessEnum
    {
        return $this->access;
    }

    public function setAccess(MountPointAccessEnum $access): static
    {
        $this->access = $access;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
