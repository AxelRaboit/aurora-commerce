<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Serializer;

use Aurora\Module\Erp\Product\Entity\ProductInterface;

interface ProductSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ProductInterface $product): array;
}
