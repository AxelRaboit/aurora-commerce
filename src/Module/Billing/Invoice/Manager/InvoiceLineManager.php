<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(InvoiceLineManagerInterface::class)]
class InvoiceLineManager implements InvoiceLineManagerInterface
{
    use ScalarCoercionTrait;

    /** @var array<string, callable> */
    protected readonly array $fieldSetters;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {
        $this->fieldSetters = [
            // Label is NOT NULL in DB; collapse empty/whitespace to empty string instead of null.
            'label' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setLabel($this->stringOrNull($value) ?? ''),
            'productCode' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setProductCode($this->stringOrNull($value)),
            'unit' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setUnit($this->stringOrNull($value)),
            'quantity' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setQuantity($this->stringOrNull($value) ?? '1.0000'),
            'unitPriceCents' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setUnitPriceCents($this->intOrNull($value, 'backend.billing.invoices.update.notNumeric')),
            'vatRateBp' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setVatRateBp($this->intOrNull($value, 'backend.billing.invoices.update.notNumeric')),
            'totalNetCents' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setTotalNetCents($this->intOrNull($value, 'backend.billing.invoices.update.notNumeric')),
            'totalGrossCents' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setTotalGrossCents($this->intOrNull($value, 'backend.billing.invoices.update.notNumeric')),
            'reference' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setReference($this->stringOrNull($value)),
            'description' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setDescription($this->stringOrNull($value)),
            'discountCents' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setDiscountCents($this->intOrNull($value, 'backend.billing.invoices.update.notNumeric')),
            'origin' => fn (InvoiceLineInterface $line, mixed $value): InvoiceLineInterface => $line->setOrigin($this->stringOrNull($value)),
        ];
    }

    public function add(InvoiceInterface $invoice): InvoiceLineInterface
    {
        $invoice->assertEditable();

        $line = $this->createInvoiceLine();
        $line->setLabel('');
        $line->setQuantity('1.0000');
        $line->setPosition($invoice->getLines()->count());

        $invoice->addLine($line);

        $this->entityManager->persist($line);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.line.added', 'InvoiceLine', $line->getId(), [
            ...$this->auditPayload($line),
            'invoiceId' => $invoice->getId(),
        ]);

        return $line;
    }

    public function updateField(InvoiceLineInterface $line, string $field, mixed $value): void
    {
        $line->getInvoice()?->assertEditable();

        $setter = $this->fieldSetters[$field] ?? null;
        if (null === $setter) {
            throw new InvalidArgumentException('backend.billing.invoices.update.unknownField');
        }

        $setter($line, $value);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'invoice.line.updated', 'InvoiceLine', $line->getId(), [
            ...$this->auditPayload($line),
            'field' => $field,
        ]);
    }

    public function delete(InvoiceLineInterface $line): void
    {
        $line->getInvoice()?->assertEditable();

        $invoiceId = $line->getInvoice()?->getId();
        $this->auditLogger->log('billing', 'invoice.line.deleted', 'InvoiceLine', $line->getId(), [
            ...$this->auditPayload($line),
            'invoiceId' => $invoiceId,
        ]);

        $this->entityManager->remove($line);
        $this->entityManager->flush();
    }

    protected function createInvoiceLine(): InvoiceLineInterface
    {
        return new InvoiceLine();
    }

    /**
     * Base payload for every InvoiceLine audit entry. Override to add custom
     * fields that survive splat-merge in line.added/updated/deleted events.
     *
     * Note: InvoiceLine has no standard create/update/delete triplet — its
     * lifecycle uses domain events (added, updated:field, deleted) so the
     * `auditCreated/Updated/Deleted` hooks do not apply. The payload still
     * stays extensible via splat-merge in each event.
     */
    protected function auditPayload(InvoiceLineInterface $line): array
    {
        return ['label' => $line->getLabel()];
    }
}
