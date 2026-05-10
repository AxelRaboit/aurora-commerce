<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Serializer;

use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;

interface CartSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(?CartInterface $cart): array;
}
