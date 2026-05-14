<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Dto;

interface ListingCategoryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ListingCategoryInputInterface;
}
