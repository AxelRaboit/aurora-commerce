<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Cart\Manager\CartManagerInterface;
use Aurora\Module\Ecommerce\Order\Dto\CheckoutInputInterface;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Event\OrderCreatedEvent;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\Service\OrderNotificationService;
use Aurora\Module\Ecommerce\Order\Service\OrderRefundService;
use Aurora\Module\Erp\Product\Entity\Product;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsAlias(OrderManagerInterface::class)]
class OrderManager implements OrderManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly OrderRepository $orderRepository,
        protected readonly CartManagerInterface $cartManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly OrderNotificationService $notificationService,
        protected readonly OrderRefundService $refundService,
        protected readonly SettingRepository $settingRepository,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function createFromCart(CartInterface $cart, CheckoutInputInterface $input, ?CoreUserInterface $customer, string $locale = 'fr'): Order
    {
        if (0 === $cart->getItems()->count()) {
            throw new InvalidArgumentException('Cart is empty');
        }

        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getListing()->getProduct();
            if (!$product->isInStock($cartItem->getQuantity())) {
                throw new InvalidArgumentException(sprintf('Insufficient stock for "%s" (requested: %d, available: %s)', $cartItem->getListing()->getDisplayTitle(), $cartItem->getQuantity(), null === $product->getStockQuantity() ? '∞' : (string) $product->getStockQuantity()));
            }
        }

        $order = $this->createOrder();
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::EcommerceOrderPrefix->value, SequencePrefixEnum::Order->value);
        $order->setNumber($this->orderRepository->getNextOrderNumber($prefix ?? SequencePrefixEnum::Order->value));
        $order->setToken(bin2hex(random_bytes(16)));
        $order->setCustomer($customer);
        $order->setStatus(OrderStatusEnum::Pending);
        $order->setEmail($input->getEmail());
        $order->setName($input->getName());
        $order->setAddressLine1($input->getAddressLine1());
        $order->setAddressLine2($input->getAddressLine2());
        $order->setCity($input->getCity());
        $order->setPostalCode($input->getPostalCode());
        $order->setCountryEnum($input->getCountry());
        $order->setNotes($input->getNotes());
        $order->setLocale($locale);

        $totalCents = 0;
        $currency = null;
        foreach ($cart->getItems() as $cartItem) {
            $listing = $cartItem->getListing();
            $line = $this->createOrderLine()
                ->setListing($listing)
                ->setTitleSnapshot($listing->getDisplayTitle())
                ->setReferenceSnapshot($listing->getProduct()->getReference())
                ->setQuantity($cartItem->getQuantity())
                ->setUnitPriceCents($cartItem->getUnitPriceCents())
                ->setCurrency($cartItem->getCurrency());
            $order->addLine($line);
            $this->entityManager->persist($line);
            $totalCents += $line->getSubtotalCents();
            $currency = $cartItem->getCurrency();
        }

        $order->setTotalCents($totalCents);
        if (null !== $currency) {
            $order->setCurrency($currency);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $linePrefix = $this->settingRepository->get(ApplicationParameterEnum::EcommerceOrderLinePrefix->value, SequencePrefixEnum::OrderLine->value) ?? SequencePrefixEnum::OrderLine->value;
        foreach ($order->getLines() as $line) {
            $line->setReference($this->sequenceGenerator->next($linePrefix));
        }

        $this->entityManager->flush();

        $this->auditLogger->log('ecommerce', 'order.created', 'Order', $order->getId(), [
            ...$this->auditPayload($order),
            'totalCents' => $totalCents,
        ]);

        $this->eventDispatcher->dispatch(new OrderCreatedEvent($order));

        return $order;
    }

    /**
     * Marks an order paid and decrements stock atomically. Re-acquires PESSIMISTIC_WRITE locks
     * on each product so the call is safe whether invoked from checkout() (already locked) or
     * from a webhook/admin action (no prior lock). Doctrine treats nested wrapInTransaction
     * as a savepoint, so the double lock is cheap when nested.
     */
    public function markPaid(Order $order): void
    {
        if (OrderStatusEnum::Pending !== $order->getStatus()) {
            return;
        }

        $this->entityManager->wrapInTransaction(function () use ($order): void {
            $quantitiesByProductId = $this->aggregateOrderQuantities($order);

            foreach ($quantitiesByProductId as $productId => $totalQuantity) {
                $product = $this->entityManager->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
                if (null === $product) {
                    throw new InvalidArgumentException(sprintf('Product %d no longer exists', $productId));
                }

                if (!$product->isInStock($totalQuantity)) {
                    throw new InvalidArgumentException(sprintf('Insufficient stock for "%s" (requested: %d, available: %s)', $product->getName(), $totalQuantity, null === $product->getStockQuantity() ? '∞' : (string) $product->getStockQuantity()));
                }

                $product->decrementStock($totalQuantity);
            }

            $order->setStatus(OrderStatusEnum::Paid);
            $this->entityManager->flush();
        });

        $this->auditLogger->log('ecommerce', 'order.paid', 'Order', $order->getId(), $this->auditPayload($order));

        $this->notificationService->notifyPaid($order);
    }

    public function markShipped(Order $order): void
    {
        $this->transitionStatus($order, OrderStatusEnum::Shipped, [OrderStatusEnum::Paid], 'order.shipped');
        $this->notificationService->notifyShipped($order);
    }

    public function markDelivered(Order $order): void
    {
        $this->transitionStatus($order, OrderStatusEnum::Delivered, [OrderStatusEnum::Shipped, OrderStatusEnum::Paid], 'order.delivered');
    }

    /**
     * Cancels an order. If the order was paid (and not already refunded), the Stripe payment
     * is refunded automatically. Already-refunded orders skip the refund step (just mark
     * cancelled). Idempotent for already-cancelled orders.
     */
    public function cancel(Order $order): void
    {
        if (OrderStatusEnum::Cancelled === $order->getStatus() || OrderStatusEnum::Delivered === $order->getStatus()) {
            return;
        }

        $previous = $order->getStatus();
        $alreadyRefunded = OrderStatusEnum::Refunded === $previous;

        // Auto-refund: Paid/Shipped order with a Stripe payment intent that hasn't been refunded yet.
        $needsRefund = !$alreadyRefunded
            && null !== $order->getStripePaymentIntentId()
            && in_array($previous, [OrderStatusEnum::Paid, OrderStatusEnum::Shipped], true);

        if ($needsRefund) {
            $this->refundService->refundForCancel($order);
        }

        $this->entityManager->wrapInTransaction(function () use ($order, $previous, $alreadyRefunded): void {
            // Restock only if stock was actually decremented AND not already refunded
            // (a previous refund already restocked, don't double-restock).
            $shouldRestock = !$alreadyRefunded
                && in_array($previous, [OrderStatusEnum::Paid, OrderStatusEnum::Shipped], true);

            if ($shouldRestock) {
                $quantitiesByProductId = $this->aggregateOrderQuantities($order);
                foreach ($quantitiesByProductId as $productId => $totalQuantity) {
                    $product = $this->entityManager->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
                    if (null !== $product && $product->isStockTracked()) {
                        $product->setStockQuantity(($product->getStockQuantity() ?? 0) + $totalQuantity);
                    }
                }
            }

            $order->setStatus(OrderStatusEnum::Cancelled);
            $this->entityManager->flush();
        });

        $this->auditLogger->log('ecommerce', 'order.cancelled', 'Order', $order->getId(), [
            ...$this->auditPayload($order),
            'from' => $previous->value,
            'refunded' => $needsRefund,
            'alreadyRefunded' => $alreadyRefunded,
        ]);

        $this->notificationService->notifyCancelled($order, $needsRefund || $alreadyRefunded);
    }

    /**
     * Generic forward-only status transition. Throws when the requested transition is not allowed —
     * surface this as a 422 in controllers.
     *
     * @param array<int, OrderStatusEnum> $allowedFrom
     */
    private function transitionStatus(Order $order, OrderStatusEnum $to, array $allowedFrom, string $auditAction): void
    {
        if ($order->getStatus() === $to) {
            return;
        }

        if (!in_array($order->getStatus(), $allowedFrom, true)) {
            throw new InvalidArgumentException(sprintf('Cannot transition order %s from %s to %s', $order->getNumber(), $order->getStatus()->value, $to->value));
        }

        $from = $order->getStatus();
        $order->setStatus($to);
        $this->entityManager->flush();

        $this->auditLogger->log('ecommerce', $auditAction, 'Order', $order->getId(), [
            ...$this->auditPayload($order),
            'from' => $from->value,
        ]);
    }

    /**
     * Atomic checkout: lock all product rows, validate stock, create order, decrement, mark paid.
     * Wrapped in a transaction so any failure (concurrent stock conflict, validation, payment stub)
     * rolls back everything — no partial state left in DB.
     *
     * Pessimistic write lock prevents two concurrent checkouts from both passing stock validation
     * and oversold stock. The 2nd transaction blocks until the 1st commits, then re-reads the
     * decremented value and fails cleanly with InvalidArgumentException.
     */
    public function checkout(CartInterface $cart, CheckoutInputInterface $input, ?CoreUserInterface $customer): Order
    {
        if (0 === $cart->getItems()->count()) {
            throw new InvalidArgumentException('Cart is empty');
        }

        $order = $this->entityManager->wrapInTransaction(function () use ($cart, $input, $customer): Order {
            $this->lockAndValidateStock($cart);
            $order = $this->createFromCart($cart, $input, $customer);
            $this->markPaid($order); // Stub: auto-pay for MVP. Real payment integration later.

            return $order;
        });

        $this->cartManager->clear();

        return $order;
    }

    /**
     * Acquires a pessimistic WRITE lock on each product in the cart and re-validates stock
     * within the locked snapshot. Must be called inside a transaction.
     */
    private function lockAndValidateStock(CartInterface $cart): void
    {
        $quantitiesByProductId = [];
        foreach ($cart->getItems() as $cartItem) {
            $productId = (int) $cartItem->getListing()->getProduct()->getId();
            $quantitiesByProductId[$productId] = ($quantitiesByProductId[$productId] ?? 0) + $cartItem->getQuantity();
        }

        // Deterministic lock acquisition order — prevents deadlocks
        // when two concurrent checkouts share multiple products (A,B vs B,A).
        ksort($quantitiesByProductId);

        foreach ($quantitiesByProductId as $productId => $totalQuantity) {
            $product = $this->entityManager->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
            if (null === $product) {
                throw new InvalidArgumentException(sprintf('Product %d no longer exists', $productId));
            }

            if (!$product->isInStock($totalQuantity)) {
                throw new InvalidArgumentException(sprintf('Insufficient stock for "%s" (requested: %d, available: %s)', $product->getName(), $totalQuantity, null === $product->getStockQuantity() ? '∞' : (string) $product->getStockQuantity()));
            }
        }
    }

    /** @return array<int, int> productId => totalQuantity, sorted by productId for deadlock-free locking */
    private function aggregateOrderQuantities(Order $order): array
    {
        $quantitiesByProductId = [];
        foreach ($order->getLines() as $line) {
            $listing = $line->getListing();
            if (null === $listing) {
                continue;
            }

            $productId = (int) $listing->getProduct()->getId();
            $quantitiesByProductId[$productId] = ($quantitiesByProductId[$productId] ?? 0) + $line->getQuantity();
        }

        ksort($quantitiesByProductId);

        return $quantitiesByProductId;
    }

    /**
     * Instantiates the concrete Order entity. Override in a subclass to
     * return `App\Entity\Order` (or any class implementing `OrderInterface`)
     * — `resolve_target_entities` only affects Doctrine relations, not
     * direct `new`.
     */
    protected function createOrder(): Order
    {
        return new Order();
    }

    /**
     * Instantiates the concrete OrderLine entity. Override in a subclass to
     * return `App\Entity\OrderLine`. Called once per cart item from
     * `createFromCart()` — the cascade extension hook for line items.
     */
    protected function createOrderLine(): OrderLine
    {
        return new OrderLine();
    }

    /**
     * Base payload logged on every audit entry for an Order. Override to add
     * custom fields:
     * `[...parent::auditPayload($order), 'code' => $order->getCode()]`.
     *
     * Note: Order has no standard `update()`/`delete()` flow, so the
     * `auditCreated/Updated/Deleted` triplet does not apply here. Instead,
     * domain events (paid, shipped, cancelled, …) call `$this->auditPayload()`
     * inline to stay extensible.
     */
    protected function auditPayload(Order $order): array
    {
        return ['number' => $order->getNumber()];
    }
}
