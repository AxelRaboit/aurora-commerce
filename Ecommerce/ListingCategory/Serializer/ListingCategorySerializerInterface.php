<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Serializer;

use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;

interface ListingCategorySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ListingCategoryInterface $category, ?string $locale = null): array;
}
