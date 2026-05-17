<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Service;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Payment\StripeService;
use Aurora\Module\Erp\Product\Entity\Product;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Stripe\Exception\ApiErrorException;

/**
 * Owns the refund domain — both customer-initiated (Stripe call required)
 * and webhook-driven flows (Stripe already refunded externally, just mark state).
 *
 * Split out of OrderManager so transition logic, stock management, and refund
 * orchestration can each be tested in isolation. OrderManager delegates here
 * for cancel-induced refunds.
 */
readonly class OrderRefundService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StripeService $stripeService,
        private AuditLogger $auditLogger,
        private OrderNotificationService $notificationService,
    ) {}

    /**
     * Initiate a refund: call Stripe, then apply the local state change.
     * Throws RuntimeException if the Stripe API call fails — the local state
     * stays untouched so the operation is idempotent on retry.
     */
    public function refund(Order $order, ?int $amountCents = null): void
    {
        $paymentIntentId = $order->getStripePaymentIntentId();
        if (null === $paymentIntentId) {
            throw new RuntimeException('Order has no Stripe payment intent — cannot refund');
        }

        try {
            $this->stripeService->createRefund($paymentIntentId, $amountCents);
        } catch (ApiErrorException $apiErrorException) {
            throw new RuntimeException(sprintf('Stripe refund failed: %s', $apiErrorException->getMessage()), 0, $apiErrorException);
        }

        $this->markRefunded($order, $amountCents);
    }

    /**
     * Apply refund state without calling Stripe. Used by:
     *   - OrderRefundService::refund() after a successful Stripe call
     *   - The Stripe webhook when a refund happened externally (admin clicked Refund in Stripe dashboard)
     *   - OrderManager::cancel() when restock is handled by the cancel flow itself
     *
     * Idempotent — already-refunded orders are skipped.
     */
    public function markRefunded(Order $order, ?int $amountCents = null): void
    {
        if (OrderStatusEnum::Refunded === $order->getStatus()) {
            return;
        }

        $previous = $order->getStatus();
        $isFullRefund = null === $amountCents || $amountCents >= $order->getTotalCents();

        $this->entityManager->wrapInTransaction(function () use ($order, $previous, $amountCents, $isFullRefund): void {
            // Restock only on full refund of an order that previously decremented stock.
            if ($isFullRefund && in_array($previous, [OrderStatusEnum::Paid, OrderStatusEnum::Shipped, OrderStatusEnum::Delivered], true)) {
                foreach ($this->aggregateQuantities($order) as $productId => $totalQuantity) {
                    $product = $this->entityManager->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
                    if (null !== $product && $product->isStockTracked()) {
                        $product->setStockQuantity(($product->getStockQuantity() ?? 0) + $totalQuantity);
                    }
                }
            }

            $order->setRefundedCents($amountCents ?? $order->getTotalCents());
            $order->setStatus(OrderStatusEnum::Refunded);

            $this->entityManager->flush();
        });

        $this->auditLogger->log('ecommerce', 'order.refunded', 'Order', $order->getId(), [
            'number' => $order->getNumber(),
            'from' => $previous->value,
            'amountCents' => $amountCents ?? $order->getTotalCents(),
            'fullRefund' => $isFullRefund,
        ]);

        $this->notificationService->notifyRefund($order, $amountCents ?? $order->getTotalCents(), $isFullRefund);
    }

    /**
     * Triggers ONLY the Stripe-side refund without mutating local state — used by
     * OrderManager::cancel() when the cancel flow itself owns the status/stock update
     * (we just need Stripe to refund and refundedCents to be set on the order).
     *
     * Throws RuntimeException on Stripe API failure.
     */
    public function refundForCancel(Order $order): void
    {
        $paymentIntentId = $order->getStripePaymentIntentId();
        if (null === $paymentIntentId) {
            return;
        }

        try {
            $this->stripeService->createRefund($paymentIntentId);
        } catch (ApiErrorException $apiErrorException) {
            throw new RuntimeException(sprintf('Stripe refund failed during cancel: %s', $apiErrorException->getMessage()), 0, $apiErrorException);
        }

        $order->setRefundedCents($order->getTotalCents());
    }

    /**
     * @return array<int, int> productId => totalQuantity, sorted by productId for deadlock-free locking
     */
    private function aggregateQuantities(Order $order): array
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
}
