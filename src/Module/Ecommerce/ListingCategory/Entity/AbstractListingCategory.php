<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractListingCategory implements ListingCategoryInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: ListingCategoryInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?ListingCategoryInterface $parent = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaInterface $image = null;

    #[ORM\Column(options: ['default' => true])]
    protected bool $isVisible = true;

    public function getParent(): ?ListingCategoryInterface
    {
        return $this->parent;
    }

    public function setParent(?ListingCategoryInterface $parent): static
    {
        $this->parent = $parent;

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

    public function getImage(): ?MediaInterface
    {
        return $this->image;
    }

    public function setImage(?MediaInterface $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setVisible(bool $visible): static
    {
        $this->isVisible = $visible;

        return $this;
    }

    public function isRoot(): bool
    {
        return !$this->parent instanceof ListingCategoryInterface;
    }

    public function getDepth(): int
    {
        $depth = 0;
        $current = $this->parent;
        while ($current instanceof ListingCategoryInterface) {
            ++$depth;
            $current = $current->getParent();
        }

        return $depth;
    }
}
