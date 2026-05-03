<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Entity;

use Aurora\Module\Billing\Invoice\Repository\InvoiceLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceLineRepository::class)]
#[ORM\Table(name: 'billing_invoice_lines')]
class InvoiceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_invoice_line_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Invoice $invoice = null;

    #[ORM\Column(length: 500)]
    private string $label;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $productCode = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, options: ['default' => '1.0000'])]
    private string $quantity = '1.0000';

    #[ORM\Column(nullable: true)]
    private ?int $unitPriceCents = null;

    /** VAT rate in basis points (e.g. 2000 = 20.00%). Null = unknown. */
    #[ORM\Column(nullable: true)]
    private ?int $vatRateBp = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalNetCents = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalGrossCents = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $discountCents = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $origin = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(?string $productCode): self
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPriceCents(): ?int
    {
        return $this->unitPriceCents;
    }

    public function setUnitPriceCents(?int $unitPriceCents): self
    {
        $this->unitPriceCents = $unitPriceCents;

        return $this;
    }

    public function getVatRateBp(): ?int
    {
        return $this->vatRateBp;
    }

    public function setVatRateBp(?int $vatRateBp): self
    {
        $this->vatRateBp = $vatRateBp;

        return $this;
    }

    public function getTotalNetCents(): ?int
    {
        return $this->totalNetCents;
    }

    public function setTotalNetCents(?int $totalNetCents): self
    {
        $this->totalNetCents = $totalNetCents;

        return $this;
    }

    public function getTotalGrossCents(): ?int
    {
        return $this->totalGrossCents;
    }

    public function setTotalGrossCents(?int $totalGrossCents): self
    {
        $this->totalGrossCents = $totalGrossCents;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDiscountCents(): ?int
    {
        return $this->discountCents;
    }

    public function setDiscountCents(?int $discountCents): self
    {
        $this->discountCents = $discountCents;

        return $this;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(?string $origin): self
    {
        $this->origin = $origin;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
