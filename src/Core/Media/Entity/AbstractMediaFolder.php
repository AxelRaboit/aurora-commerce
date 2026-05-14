<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMediaFolder implements MediaFolderInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 150)]
    protected string $name;

    #[ORM\ManyToOne(targetEntity: MediaFolderInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaFolderInterface $parent = null;

    /** @var Collection<int, MediaFolderInterface> */
    #[ORM\OneToMany(targetEntity: MediaFolderInterface::class, mappedBy: 'parent')]
    protected Collection $children;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

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

    public function getParent(): ?MediaFolderInterface
    {
        return $this->parent;
    }

    public function setParent(?MediaFolderInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
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

    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        while ($current instanceof MediaFolderInterface) {
            array_unshift($ancestors, $current);
            $current = $current->getParent();
        }

        return $ancestors;
    }

    public function isDescendantOf(MediaFolderInterface $candidate): bool
    {
        return in_array($candidate, $this->getAncestors(), true);
    }
}
