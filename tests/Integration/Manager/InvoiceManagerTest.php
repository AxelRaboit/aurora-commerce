<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Manager;

use Aurora\Module\Billing\Invoice\Contract\InvoiceManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class InvoiceManagerTest extends IntegrationTestCase
{
    private InvoiceManagerInterface $manager;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->manager = static::getContainer()->get(InvoiceManagerInterface::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function makeInvoice(): Invoice
    {
        $invoice = new Invoice();
        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    public function testValidateMovesStatusToValidated(): void
    {
        $invoice = $this->makeInvoice();

        $this->manager->validate($invoice);

        self::assertSame(InvoiceStatusEnum::Validated, $invoice->getStatus());
        $this->em->refresh($invoice);
        self::assertSame(InvoiceStatusEnum::Validated, $invoice->getStatus());
    }

    public function testUpdateFieldStringTrimsAndPersists(): void
    {
        $invoice = $this->makeInvoice();

        $this->manager->updateField($invoice, 'number', '  INV-007 ');

        self::assertSame('INV-007', $invoice->getNumber());
    }

    public function testUpdateFieldEmptyStringBecomesNull(): void
    {
        $invoice = $this->makeInvoice();
        $invoice->setNumber('SET');
        $this->em->flush();

        $this->manager->updateField($invoice, 'number', '');

        self::assertNull($invoice->getNumber());
    }

    public function testUpdateFieldDateParses(): void
    {
        $invoice = $this->makeInvoice();

        $this->manager->updateField($invoice, 'issuedAt', '2025-12-31');

        self::assertEquals(new DateTimeImmutable('2025-12-31'), $invoice->getIssuedAt());
    }

    public function testUpdateFieldInvalidDateThrows(): void
    {
        $invoice = $this->makeInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('backend.billing.invoices.update.invalidDate');

        $this->manager->updateField($invoice, 'issuedAt', 'not-a-date');
    }

    public function testUpdateFieldUnknownFieldThrows(): void
    {
        $invoice = $this->makeInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('backend.billing.invoices.update.unknownField');

        $this->manager->updateField($invoice, 'status', 'paid');
    }

    public function testUpdateFieldMoneyIntegerCoercion(): void
    {
        $invoice = $this->makeInvoice();

        $this->manager->updateField($invoice, 'totalGrossCents', '1500');

        self::assertSame(1500, $invoice->getTotalGrossCents());
    }

    public function testUpdateFieldMoneyNonNumericThrows(): void
    {
        $invoice = $this->makeInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('backend.billing.invoices.update.notNumeric');

        $this->manager->updateField($invoice, 'totalGrossCents', 'twelve');
    }

    public function testDeleteRemovesInvoice(): void
    {
        $invoice = $this->makeInvoice();
        $id = $invoice->getId();

        $this->manager->delete($invoice);

        self::assertNull($this->em->find(Invoice::class, $id));
    }
}
