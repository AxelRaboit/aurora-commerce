<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Entity;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Doctrine\Common\Collections\Collection;

interface ListingCategoryInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getParent(): ?ListingCategoryInterface;

    public function setParent(?ListingCategoryInterface $parent): static;

    /** @return Collection<int, ListingCategoryInterface> */
    public function getChildren(): Collection;

    public function addChild(ListingCategoryInterface $child): static;

    public function removeChild(ListingCategoryInterface $child): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getImage(): ?MediaInterface;

    public function setImage(?MediaInterface $image): static;

    public function isVisible(): bool;

    public function setVisible(bool $visible): static;

    /** @return Collection<string, ListingCategoryTranslationInterface> */
    public function getTranslations(): Collection;

    public function getTranslation(string $locale): ?ListingCategoryTranslationInterface;

    public function addTranslation(ListingCategoryTranslationInterface $translation): static;

    public function removeTranslation(ListingCategoryTranslationInterface $translation): static;

    public function translate(string $locale): ListingCategoryTranslationInterface;

    public function isRoot(): bool;

    public function getDepth(): int;

    /** @return Collection<int, ListingInterface> */
    public function getListings(): Collection;
}
