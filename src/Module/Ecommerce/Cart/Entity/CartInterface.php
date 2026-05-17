<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\User;
use Doctrine\Common\Collections\Collection;

interface CartInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getSessionId(): ?string;

    public function setSessionId(?string $sessionId): static;

    public function getUser(): ?User;

    public function setUser(?User $user): static;

    /** @return Collection<int, CartItemInterface> */
    public function getItems(): Collection;

    public function addItem(CartItemInterface $item): static;

    public function removeItem(CartItemInterface $item): static;

    public function getTotalQuantity(): int;

    public function getTotalCents(): int;
}
