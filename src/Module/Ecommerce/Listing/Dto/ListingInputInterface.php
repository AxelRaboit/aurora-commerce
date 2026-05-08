<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Dto;

interface ListingInputInterface
{
    public function getProductId(): ?int;

    public function getSlug(): string;

    public function getMarketingTitle(): ?string;

    public function getMarketingDescription(): ?string;

    public function getFeaturedImageId(): ?int;

    public function isVisibleOnShop(): bool;

    public function getSeoTitle(): ?string;

    public function getSeoDescription(): ?string;
}
