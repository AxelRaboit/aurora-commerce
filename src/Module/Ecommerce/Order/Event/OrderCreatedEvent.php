<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Event;

use Aurora\Module\Ecommerce\Order\Entity\OrderInterface;

/**
 * Dispatched after an order has been persisted and flushed in createFromCart().
 * Listeners (CRM sync, accounting export, ...) attach to it.
 */
class OrderCreatedEvent
{
    public function __construct(
        private readonly OrderInterface $order,
    ) {}

    public function getOrder(): OrderInterface
    {
        return $this->order;
    }
}
