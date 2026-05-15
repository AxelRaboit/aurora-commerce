<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Order\Entity\OrderInterface;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use PHPUnit\Framework\TestCase;

final class OrderLineTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new OrderLine())->getId());
    }

    public function testDefaultValues(): void
    {
        $line = new OrderLine();

        self::assertNull($line->getReference());
        self::assertNull($line->getOrder());
        self::assertNull($line->getListing());
        self::assertSame(1, $line->getQuantity());
        self::assertSame(0, $line->getUnitPriceCents());
        self::assertSame(CurrencyEnum::EUR, $line->getCurrency());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $line = (new OrderLine())->setReference('ORD-LINE-001');

        self::assertSame('ORD-LINE-001', $line->getReference());

        $line->setReference(null);
        self::assertNull($line->getReference());
    }

    public function testOrderGetterAndSetter(): void
    {
        $order = $this->createStub(OrderInterface::class);
        $line = (new OrderLine())->setOrder($order);

        self::assertSame($order, $line->getOrder());

        $line->setOrder(null);
        self::assertNull($line->getOrder());
    }

    public function testListingGetterAndSetter(): void
    {
        $listing = $this->createStub(ListingInterface::class);
        $line = (new OrderLine())->setListing($listing);

        self::assertSame($listing, $line->getListing());

        $line->setListing(null);
        self::assertNull($line->getListing());
    }

    public function testSnapshotGettersAndSetters(): void
    {
        $line = (new OrderLine())
            ->setTitleSnapshot('Widget Pro')
            ->setReferenceSnapshot('WGT-PRO-001');

        self::assertSame('Widget Pro', $line->getTitleSnapshot());
        self::assertSame('WGT-PRO-001', $line->getReferenceSnapshot());
    }

    public function testQuantityMinimumIsOne(): void
    {
        $line = new OrderLine();

        $line->setQuantity(3);
        self::assertSame(3, $line->getQuantity());

        $line->setQuantity(0);
        self::assertSame(1, $line->getQuantity(), 'quantity below 1 is clamped to 1');

        $line->setQuantity(-5);
        self::assertSame(1, $line->getQuantity(), 'negative quantity is clamped to 1');
    }

    public function testUnitPriceCentsGetterAndSetter(): void
    {
        $line = (new OrderLine())->setUnitPriceCents(1999);

        self::assertSame(1999, $line->getUnitPriceCents());
    }

    public function testCurrencyGetterAndSetter(): void
    {
        $line = (new OrderLine())->setCurrency(CurrencyEnum::USD);

        self::assertSame(CurrencyEnum::USD, $line->getCurrency());
    }

    public function testGetSubtotalCentsIsUnitPriceTimesQuantity(): void
    {
        $line = (new OrderLine())->setUnitPriceCents(500)->setQuantity(3);

        self::assertSame(1500, $line->getSubtotalCents());
    }

    public function testSettersReturnSelf(): void
    {
        $line = new OrderLine();

        self::assertSame($line, $line->setReference('r'));
        self::assertSame($line, $line->setOrder($this->createStub(OrderInterface::class)));
        self::assertSame($line, $line->setListing($this->createStub(ListingInterface::class)));
        self::assertSame($line, $line->setTitleSnapshot('t'));
        self::assertSame($line, $line->setReferenceSnapshot('r'));
        self::assertSame($line, $line->setQuantity(1));
        self::assertSame($line, $line->setUnitPriceCents(100));
        self::assertSame($line, $line->setCurrency(CurrencyEnum::GBP));
    }
}
