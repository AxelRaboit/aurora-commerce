<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Serializer;

use Aurora\Module\Ecommerce\Order\Entity\OrderInterface;

interface OrderSerializerInterface
{
    /**
     * Compact projection for admin list rows — no lines, no addresses.
     *
     * @return array<string, mixed>
     */
    public function serializeForList(OrderInterface $order): array;

    /** @return array<string, mixed> */
    public function serialize(OrderInterface $order): array;
}
