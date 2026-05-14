<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractVaultFolder implements VaultFolderInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    #[ORM\Column(length: 100)]
    protected string $name;

    #[ORM\Column(length: 7, nullable: true)]
    protected ?string $color = null;

    #[ORM\Column]
    protected int $position = 0;

    #[ORM\ManyToOne(targetEntity: VaultFolderInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?VaultFolderInterface $parent = null;

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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getParent(): ?VaultFolderInterface
    {
        return $this->parent;
    }

    public function setParent(?VaultFolderInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}
