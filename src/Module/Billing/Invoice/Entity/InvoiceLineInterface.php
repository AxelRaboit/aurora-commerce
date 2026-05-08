<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Entity;

interface InvoiceLineInterface
{
    public function getId(): ?int;

    public function getInvoice(): ?InvoiceInterface;

    public function setInvoice(?InvoiceInterface $invoice): self;

    public function getLabel(): string;

    public function setLabel(string $label): self;

    public function getProductCode(): ?string;

    public function setProductCode(?string $productCode): self;

    public function getUnit(): ?string;

    public function setUnit(?string $unit): self;

    public function getQuantity(): string;

    public function setQuantity(string $quantity): self;

    public function getUnitPriceCents(): ?int;

    public function setUnitPriceCents(?int $unitPriceCents): self;

    public function getVatRateBp(): ?int;

    public function setVatRateBp(?int $vatRateBp): self;

    public function getTotalNetCents(): ?int;

    public function setTotalNetCents(?int $totalNetCents): self;

    public function getTotalGrossCents(): ?int;

    public function setTotalGrossCents(?int $totalGrossCents): self;

    public function getReference(): ?string;

    public function setReference(?string $reference): self;

    public function getDescription(): ?string;

    public function setDescription(?string $description): self;

    public function getDiscountCents(): ?int;

    public function setDiscountCents(?int $discountCents): self;

    public function getOrigin(): ?string;

    public function setOrigin(?string $origin): self;

    public function getPosition(): int;

    public function setPosition(int $position): self;
}
