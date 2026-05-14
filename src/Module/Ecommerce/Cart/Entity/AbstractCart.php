<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Core\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractCart implements CartInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $sessionId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected ?User $user = null;

    /** @var Collection<int, CartItemInterface> */
    #[ORM\OneToMany(targetEntity: CartItemInterface::class, mappedBy: 'cart', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
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

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CartItemInterface $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCart($this);
        }

        return $this;
    }

    public function removeItem(CartItemInterface $item): static
    {
        if ($this->items->removeElement($item) && $item->getCart() === $this) {
            $item->setCart(null);
        }

        return $this;
    }

    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }

        return $total;
    }

    public function getTotalCents(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getSubtotalCents();
        }

        return $total;
    }
}
