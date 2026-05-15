<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use PHPUnit\Framework\TestCase;

final class InvoiceLineTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new InvoiceLine())->getId());
    }

    public function testDefaultValues(): void
    {
        $line = new InvoiceLine();

        self::assertNull($line->getInvoice());
        self::assertNull($line->getProductCode());
        self::assertNull($line->getUnit());
        self::assertSame('1.0000', $line->getQuantity());
        self::assertNull($line->getUnitPriceCents());
        self::assertNull($line->getVatRateBp());
        self::assertNull($line->getTotalNetCents());
        self::assertNull($line->getTotalGrossCents());
        self::assertNull($line->getReference());
        self::assertNull($line->getDescription());
        self::assertNull($line->getDiscountCents());
        self::assertNull($line->getOrigin());
        self::assertSame(0, $line->getPosition());
    }

    public function testLabelGetterAndSetter(): void
    {
        $line = (new InvoiceLine())->setLabel('Consulting');

        self::assertSame('Consulting', $line->getLabel());
    }

    public function testInvoiceGetterAndSetter(): void
    {
        $invoice = $this->createStub(InvoiceInterface::class);
        $line = (new InvoiceLine())->setInvoice($invoice);

        self::assertSame($invoice, $line->getInvoice());
    }

    public function testProductDetailsGettersAndSetters(): void
    {
        $line = (new InvoiceLine())
            ->setProductCode('CONS-01')
            ->setUnit('hour')
            ->setReference('REF-001')
            ->setDescription('Consulting work')
            ->setOrigin('project_task');

        self::assertSame('CONS-01', $line->getProductCode());
        self::assertSame('hour', $line->getUnit());
        self::assertSame('REF-001', $line->getReference());
        self::assertSame('Consulting work', $line->getDescription());
        self::assertSame('project_task', $line->getOrigin());
    }

    public function testQuantityAndPriceGettersAndSetters(): void
    {
        $line = (new InvoiceLine())
            ->setQuantity('2.5000')
            ->setUnitPriceCents(15000)
            ->setVatRateBp(2000)
            ->setTotalNetCents(37500)
            ->setTotalGrossCents(45000)
            ->setDiscountCents(500);

        self::assertSame('2.5000', $line->getQuantity());
        self::assertSame(15000, $line->getUnitPriceCents());
        self::assertSame(2000, $line->getVatRateBp());
        self::assertSame(37500, $line->getTotalNetCents());
        self::assertSame(45000, $line->getTotalGrossCents());
        self::assertSame(500, $line->getDiscountCents());
    }

    public function testPositionGetterAndSetter(): void
    {
        $line = (new InvoiceLine())->setPosition(3);

        self::assertSame(3, $line->getPosition());
    }
}
