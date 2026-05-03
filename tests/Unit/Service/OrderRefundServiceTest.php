<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Service\OrderNotificationService;
use Aurora\Module\Ecommerce\Order\Service\OrderRefundService;
use Aurora\Module\Ecommerce\Payment\StripeService;
use Aurora\Module\Erp\Product\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stripe\Exception\InvalidRequestException;

#[AllowMockObjectsWithoutExpectations]
final class OrderRefundServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private StripeService $stripeService;
    private AuditLogger $auditLogger;
    private OrderNotificationService $notificationService;
    private OrderRefundService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->stripeService = $this->createMock(StripeService::class);
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->notificationService = $this->createMock(OrderNotificationService::class);

        $this->service = new OrderRefundService(
            $this->entityManager,
            $this->stripeService,
            $this->auditLogger,
            $this->notificationService,
        );

        // Default: wrapInTransaction simply executes the callback synchronously.
        $this->entityManager->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $cb) => $cb());
    }

    public function testRefundThrowsWhenOrderHasNoPaymentIntent(): void
    {
        $order = (new Order())->setNumber('ORD-1')->setStatus(OrderStatusEnum::Paid)->setTotalCents(1000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no Stripe payment intent');

        $this->service->refund($order);
    }

    public function testRefundCallsStripeThenAppliesState(): void
    {
        $order = (new Order())->setNumber('ORD-1')
            ->setStatus(OrderStatusEnum::Paid)
            ->setTotalCents(2000)
            ->setStripePaymentIntentId('pi_123');

        $this->stripeService->expects(self::once())
            ->method('createRefund')
            ->with('pi_123', null);

        $this->notificationService->expects(self::once())->method('notifyRefund');

        $this->service->refund($order);

        self::assertSame(OrderStatusEnum::Refunded, $order->getStatus());
        self::assertSame(2000, $order->getRefundedCents());
    }

    public function testRefundWrapsStripeApiErrorIntoRuntimeException(): void
    {
        $order = (new Order())->setNumber('ORD-1')
            ->setStatus(OrderStatusEnum::Paid)
            ->setTotalCents(1000)
            ->setStripePaymentIntentId('pi_x');

        $this->stripeService->method('createRefund')
            ->willThrowException(new InvalidRequestException('boom'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stripe refund failed: boom');

        $this->service->refund($order);
    }

    public function testMarkRefundedIsIdempotentOnAlreadyRefundedOrder(): void
    {
        $order = (new Order())->setNumber('ORD-1')->setStatus(OrderStatusEnum::Refunded)->setTotalCents(500);

        $this->auditLogger->expects(self::never())->method('log');
        $this->notificationService->expects(self::never())->method('notifyRefund');

        $this->service->markRefunded($order);
    }

    public function testMarkRefundedFullRefundRestocksTrackedProducts(): void
    {
        $product = (new Product())->setStockQuantity(5);
        // Force product id without DB
        (function (): void { $this->id = 42; })->call($product);

        $listing = $this->createConfiguredMock(Listing::class, ['getProduct' => $product]);

        $line = $this->createConfiguredMock(OrderLine::class, [
            'getListing' => $listing,
            'getQuantity' => 3,
        ]);

        $order = (new Order())->setNumber('ORD-1')
            ->setStatus(OrderStatusEnum::Paid)
            ->setTotalCents(2000);
        $order->addLine($line);

        $this->entityManager->method('find')
            ->willReturn($product);

        $this->notificationService->expects(self::once())
            ->method('notifyRefund')
            ->with($order, 2000, true);

        $this->service->markRefunded($order);

        self::assertSame(8, $product->getStockQuantity());
        self::assertSame(OrderStatusEnum::Refunded, $order->getStatus());
        self::assertSame(2000, $order->getRefundedCents());
    }

    public function testMarkRefundedPartialRefundDoesNotRestock(): void
    {
        $product = (new Product())->setStockQuantity(5);
        $listing = $this->createConfiguredMock(Listing::class, ['getProduct' => $product]);
        $line = $this->createConfiguredMock(OrderLine::class, [
            'getListing' => $listing,
            'getQuantity' => 2,
        ]);

        $order = (new Order())->setNumber('ORD-1')
            ->setStatus(OrderStatusEnum::Paid)
            ->setTotalCents(2000);
        $order->addLine($line);

        $this->entityManager->expects(self::never())->method('find');

        $this->notificationService->expects(self::once())
            ->method('notifyRefund')
            ->with($order, 500, false);

        $this->service->markRefunded($order, 500);

        self::assertSame(5, $product->getStockQuantity());
        self::assertSame(500, $order->getRefundedCents());
    }

    public function testRefundForCancelOnlyMutatesRefundedCents(): void
    {
        $order = (new Order())->setNumber('ORD-1')
            ->setStatus(OrderStatusEnum::Paid)
            ->setTotalCents(1500)
            ->setStripePaymentIntentId('pi_cancel');

        $this->stripeService->expects(self::once())
            ->method('createRefund')
            ->with('pi_cancel');

        $this->notificationService->expects(self::never())->method('notifyRefund');
        $this->auditLogger->expects(self::never())->method('log');

        $this->service->refundForCancel($order);

        self::assertSame(OrderStatusEnum::Paid, $order->getStatus(), 'status untouched');
        self::assertSame(1500, $order->getRefundedCents());
    }

    public function testRefundForCancelNoOpsWhenNoPaymentIntent(): void
    {
        $order = (new Order())->setNumber('ORD-1')->setStatus(OrderStatusEnum::Paid)->setTotalCents(1000);

        $this->stripeService->expects(self::never())->method('createRefund');

        $this->service->refundForCancel($order);

        self::assertNull($order->getRefundedCents());
    }
}
