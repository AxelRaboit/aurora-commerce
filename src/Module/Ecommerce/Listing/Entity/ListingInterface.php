<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use Doctrine\Common\Collections\Collection;

interface ListingInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getProduct(): ProductInterface;

    public function setProduct(ProductInterface $product): static;

    public function getSlug(): string;

    public function setSlug(string $slug): static;

    public function getMarketingTitle(): ?string;

    public function setMarketingTitle(?string $marketingTitle): static;

    public function getDisplayTitle(): string;

    public function getMarketingDescription(): ?string;

    public function setMarketingDescription(?string $marketingDescription): static;

    public function getFeaturedImage(): ?MediaInterface;

    public function setFeaturedImage(?MediaInterface $featuredImage): static;

    public function isVisibleOnShop(): bool;

    public function setVisibleOnShop(bool $visible): static;

    public function getSeoTitle(): ?string;

    public function setSeoTitle(?string $seoTitle): static;

    public function getSeoDescription(): ?string;

    public function setSeoDescription(?string $seoDescription): static;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    /** @return Collection<int, ListingCategoryInterface> */
    public function getCategories(): Collection;

    public function addCategory(ListingCategoryInterface $category): static;

    public function removeCategory(ListingCategoryInterface $category): static;

    public function clearCategories(): static;

    /** @return Collection<int, ListingTagInterface> */
    public function getTags(): Collection;

    public function addTag(ListingTagInterface $tag): static;

    public function removeTag(ListingTagInterface $tag): static;

    public function clearTags(): static;
}
