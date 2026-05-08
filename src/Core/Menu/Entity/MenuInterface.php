<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Doctrine\Common\Collections\Collection;

interface MenuInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;

    public function getLocation(): string;

    public function setLocation(string $location): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    /** @return Collection<int, MenuItemInterface> */
    public function getItems(): Collection;

    public function addItem(MenuItemInterface $item): static;

    public function removeItem(MenuItemInterface $item): static;
}
