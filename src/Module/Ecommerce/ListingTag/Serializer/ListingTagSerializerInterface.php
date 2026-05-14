<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Serializer;

use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;

interface ListingTagSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ListingTagInterface $tag, ?string $locale = null): array;
}
