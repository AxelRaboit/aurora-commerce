<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Doctrine\Common\Collections\Collection;

interface ListingTagInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getColor(): string;

    public function setColor(string $color): static;

    public function isVisible(): bool;

    public function setVisible(bool $visible): static;

    /** @return Collection<string, ListingTagTranslationInterface> */
    public function getTranslations(): Collection;

    public function getTranslation(string $locale): ?ListingTagTranslationInterface;

    public function addTranslation(ListingTagTranslationInterface $translation): static;

    public function removeTranslation(ListingTagTranslationInterface $translation): static;
}
