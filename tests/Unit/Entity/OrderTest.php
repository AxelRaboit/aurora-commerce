<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Order())->getId());
    }

    public function testDefaultValues(): void
    {
        $order = new Order();

        self::assertSame(OrderStatusEnum::Pending, $order->getStatus());
        self::assertSame(CurrencyEnum::EUR, $order->getCurrency());
        self::assertSame(0, $order->getTotalCents());
        self::assertNull($order->getRefundedCents());
        self::assertNull($order->getStripePaymentIntentId());
        self::assertNull($order->getNotes());
    }

    public function testLinesCollectionInitialized(): void
    {
        self::assertCount(0, (new Order())->getLines());
    }

    public function testIsRefundableRequiresStripeIntentAndOpenStatus(): void
    {
        $order = new Order();

        self::assertFalse($order->isRefundable(), 'no stripe intent');

        $order->setStripePaymentIntentId('pi_abc');
        self::assertFalse($order->isRefundable(), 'pending status blocks refund');

        $order->setStatus(OrderStatusEnum::Paid);
        self::assertTrue($order->isRefundable(), 'paid with intent is refundable');

        $order->setStatus(OrderStatusEnum::Refunded);
        self::assertFalse($order->isRefundable(), 'already refunded');

        $order->setStatus(OrderStatusEnum::Cancelled);
        self::assertFalse($order->isRefundable(), 'cancelled');

        $order->setStatus(OrderStatusEnum::Shipped);
        self::assertTrue($order->isRefundable(), 'shipped is refundable');
    }

    public function testRequiresShippingReturnsFalseForEmptyOrder(): void
    {
        self::assertFalse((new Order())->requiresShipping());
    }

    public function testNumberAndTokenGettersAndSetters(): void
    {
        $order = (new Order())->setNumber('ORD-0001')->setToken('tok-xyz');

        self::assertSame('ORD-0001', $order->getNumber());
        self::assertSame('tok-xyz', $order->getToken());
    }

    public function testEmailAndNameGettersAndSetters(): void
    {
        $order = (new Order())->setEmail('jane@example.com')->setName('Jane Doe');

        self::assertSame('jane@example.com', $order->getEmail());
        self::assertSame('Jane Doe', $order->getName());
    }

    public function testTotalCentsAndCurrencyGettersAndSetters(): void
    {
        $order = (new Order())->setTotalCents(4999)->setCurrency(CurrencyEnum::USD);

        self::assertSame(4999, $order->getTotalCents());
        self::assertSame(CurrencyEnum::USD, $order->getCurrency());
    }
}
