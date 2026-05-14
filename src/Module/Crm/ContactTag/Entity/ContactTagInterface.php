<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Doctrine\Common\Collections\Collection;

interface ContactTagInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getLabel(): string;

    public function setLabel(string $label): static;

    public function getSlug(): string;

    public function setSlug(string $slug): static;

    public function getColor(): string;

    public function setColor(string $color): static;

    /** @return Collection<int, ContactInterface> */
    public function getContacts(): Collection;
}
