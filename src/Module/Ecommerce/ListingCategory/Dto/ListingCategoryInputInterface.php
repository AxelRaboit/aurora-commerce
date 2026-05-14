<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Dto;

interface ListingCategoryInputInterface
{
    public function getParentId(): ?int;

    public function getPosition(): int;

    public function getImageId(): ?int;

    public function isVisible(): bool;

    /** @return array<string, ListingCategoryTranslationInput> */
    public function getTranslations(): array;
}
