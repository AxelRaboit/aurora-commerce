<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Serializer;

use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;

interface ListingSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ListingInterface $listing): array;
}
