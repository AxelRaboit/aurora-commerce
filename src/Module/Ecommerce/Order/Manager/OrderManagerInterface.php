<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Manager;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Order\Dto\CheckoutInputInterface;
use Aurora\Module\Ecommerce\Order\Entity\Order;

interface OrderManagerInterface
{
    public function createFromCart(CartInterface $cart, CheckoutInputInterface $input, ?CoreUserInterface $customer, string $locale): Order;

    public function markPaid(Order $order): void;

    public function markShipped(Order $order): void;

    public function markDelivered(Order $order): void;

    /**
     * Cancels an order. If the order was already paid, restock the products in a transaction
     * to keep inventory consistent. Idempotent for already-cancelled orders.
     */
    public function cancel(Order $order): void;

    public function checkout(CartInterface $cart, CheckoutInputInterface $input, ?CoreUserInterface $customer, string $locale): Order;
}
