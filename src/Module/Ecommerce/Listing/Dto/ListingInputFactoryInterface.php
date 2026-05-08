<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Dto;

interface ListingInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ListingInputInterface;
}
