<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Contract;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Order\Dto\CheckoutInput;
use Aurora\Module\Ecommerce\Order\Entity\Order;

interface OrderManagerInterface
{
    public function createFromCart(Cart $cart, CheckoutInput $input, ?User $customer, string $locale = 'fr'): Order;

    public function markPaid(Order $order): void;

    public function markShipped(Order $order): void;

    public function markDelivered(Order $order): void;

    /**
     * Cancels an order. If the order was already paid, restock the products in a transaction
     * to keep inventory consistent. Idempotent for already-cancelled orders.
     */
    public function cancel(Order $order): void;

    public function checkout(Cart $cart, CheckoutInput $input, ?User $customer): Order;
}
