<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Dto;

interface ListingTagInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ListingTagInputInterface;
}
