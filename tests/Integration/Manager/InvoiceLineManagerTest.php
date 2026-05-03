<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Manager;

use Aurora\Module\Billing\Invoice\Contract\InvoiceLineManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class InvoiceLineManagerTest extends IntegrationTestCase
{
    private InvoiceLineManagerInterface $manager;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->manager = static::getContainer()->get(InvoiceLineManagerInterface::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function makeInvoice(): Invoice
    {
        $invoice = new Invoice();
        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    public function testAddCreatesLineWithSequentialPosition(): void
    {
        $invoice = $this->makeInvoice();

        $first = $this->manager->add($invoice);
        $second = $this->manager->add($invoice);

        self::assertSame(0, $first->getPosition());
        self::assertSame(1, $second->getPosition());
        self::assertSame('1.0000', $first->getQuantity());
        self::assertSame('', $first->getLabel());
        self::assertCount(2, $invoice->getLines());
    }

    public function testUpdateLabelTrimsAndCannotBeNull(): void
    {
        $line = $this->manager->add($this->makeInvoice());

        $this->manager->updateField($line, 'label', '  Service A  ');
        self::assertSame('Service A', $line->getLabel());

        // empty/null should fall back to '' (NOT NULL column)
        $this->manager->updateField($line, 'label', null);
        self::assertSame('', $line->getLabel());
    }

    public function testUpdateMoneyFields(): void
    {
        $line = $this->manager->add($this->makeInvoice());

        $this->manager->updateField($line, 'unitPriceCents', 1500);
        $this->manager->updateField($line, 'vatRateBp', '2000');
        $this->manager->updateField($line, 'totalNetCents', 4500);

        self::assertSame(1500, $line->getUnitPriceCents());
        self::assertSame(2000, $line->getVatRateBp());
        self::assertSame(4500, $line->getTotalNetCents());
    }

    public function testQuantityFallsBackToOneWhenEmptied(): void
    {
        $line = $this->manager->add($this->makeInvoice());
        $this->manager->updateField($line, 'quantity', '5');
        self::assertSame('5', $line->getQuantity());

        $this->manager->updateField($line, 'quantity', '');
        self::assertSame('1.0000', $line->getQuantity());
    }

    public function testUnknownLineFieldThrows(): void
    {
        $line = $this->manager->add($this->makeInvoice());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('admin.billing.invoices.update.unknownField');

        $this->manager->updateField($line, 'invoice', 999);
    }

    public function testDeleteRemovesLine(): void
    {
        $line = $this->manager->add($this->makeInvoice());
        $id = $line->getId();

        $this->manager->delete($line);

        self::assertNull($this->em->find(InvoiceLine::class, $id));
    }
}
