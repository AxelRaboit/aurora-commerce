<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Invoice\Contract\InvoiceLineManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(InvoiceLineManagerInterface::class)]
final readonly class InvoiceLineManager implements InvoiceLineManagerInterface
{
    use ScalarCoercionTrait;

    /** @var array<string, callable> */
    private array $fieldSetters;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
    ) {
        $this->fieldSetters = [
            // Label is NOT NULL in DB; collapse empty/whitespace to empty string instead of null.
            'label' => fn (InvoiceLine $line, mixed $value): InvoiceLine => $line->setLabel($this->stringOrNull($value) ?? ''),
            'sku' => fn (InvoiceLine $line, mixed $value): InvoiceLine => $line->setSku($this->stringOrNull($value)),
            'unit' => fn (InvoiceLine $line, mixed $value): InvoiceLine => $line->setUnit($this->stringOrNull($value)),
            'quantity' => fn (InvoiceLine $line, mixed $value): InvoiceLine => $line->setQuantity($this->stringOrNull($value) ?? '1.0000'),
            'unitPriceCents' => fn (InvoiceLine $line, mixed $value): InvoiceLine => $line->setUnitPriceCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'vatRateBp' => fn (InvoiceLine $line, mixed $value): InvoiceLine => $line->setVatRateBp($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'totalNetCents' => fn (InvoiceLine $line, mixed $value): InvoiceLine => $line->setTotalNetCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
            'totalGrossCents' => fn (InvoiceLine $line, mixed $value): InvoiceLine => $line->setTotalGrossCents($this->intOrNull($value, 'admin.billing.invoices.update.notNumeric')),
        ];
    }

    public function add(Invoice $invoice): InvoiceLine
    {
        $line = new InvoiceLine();
        $line->setLabel('');
        $line->setQuantity('1.0000');
        $line->setPosition($invoice->getLines()->count());

        $invoice->addLine($line);

        $this->entityManager->persist($line);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.line.added', 'InvoiceLine', $line->getId(), [
            'invoiceId' => $invoice->getId(),
        ]);

        return $line;
    }

    public function updateField(InvoiceLine $line, string $field, mixed $value): void
    {
        $setter = $this->fieldSetters[$field] ?? null;
        if (null === $setter) {
            throw new InvalidArgumentException('admin.billing.invoices.update.unknownField');
        }

        $setter($line, $value);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.line.updated', 'InvoiceLine', $line->getId(), [
            'field' => $field,
        ]);
    }

    public function delete(InvoiceLine $line): void
    {
        $id = $line->getId();
        $invoiceId = $line->getInvoice()?->getId();

        $this->entityManager->remove($line);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.line.deleted', 'InvoiceLine', $id, [
            'invoiceId' => $invoiceId,
        ]);
    }
}
