<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ListingInput implements ListingInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'ecommerce.listings.errors.product_required')]
        public readonly ?int $productId = null,
        #[Assert\NotBlank(message: 'ecommerce.listings.errors.slug_required')]
        #[Assert\Length(max: 200)]
        #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'ecommerce.listings.errors.slug_invalid')]
        public readonly string $slug = '',
        #[Assert\Length(max: 200)]
        public readonly ?string $marketingTitle = null,
        public readonly ?string $marketingDescription = null,
        public readonly ?int $featuredImageId = null,
        public readonly bool $isVisibleOnShop = true,
        #[Assert\Length(max: 200)]
        public readonly ?string $seoTitle = null,
        public readonly ?string $seoDescription = null,
        /** @var list<int> */
        public readonly array $categoryIds = [],
        /** @var list<int> */
        public readonly array $tagIds = [],
    ) {}

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getMarketingTitle(): ?string
    {
        return $this->marketingTitle;
    }

    public function getMarketingDescription(): ?string
    {
        return $this->marketingDescription;
    }

    public function getFeaturedImageId(): ?int
    {
        return $this->featuredImageId;
    }

    public function isVisibleOnShop(): bool
    {
        return $this->isVisibleOnShop;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    /** @return list<int> */
    public function getCategoryIds(): array
    {
        return $this->categoryIds;
    }

    /** @return list<int> */
    public function getTagIds(): array
    {
        return $this->tagIds;
    }
}
