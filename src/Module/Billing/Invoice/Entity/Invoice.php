<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Entity;

use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'core_billing_invoices')]
#[ORM\Index(name: 'idx_billing_invoice_status', columns: ['status'])]
#[ORM\Index(name: 'idx_billing_invoice_issued_at', columns: ['issued_at'])]
class Invoice extends AbstractInvoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_invoice_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
