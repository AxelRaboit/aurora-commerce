<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractDocumentFolder implements DocumentFolderInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 150)]
    protected string $name;

    #[ORM\ManyToOne(targetEntity: DocumentFolderInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?DocumentFolderInterface $parent = null;

    /** @var Collection<int, DocumentFolderInterface> */
    #[ORM\OneToMany(targetEntity: DocumentFolderInterface::class, mappedBy: 'parent')]
    protected Collection $children;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    public function __construct()
    {
        $this->children = new ArrayCollection();
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

    public function getParent(): ?DocumentFolderInterface
    {
        return $this->parent;
    }

    public function setParent(?DocumentFolderInterface $parent): static
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
}
