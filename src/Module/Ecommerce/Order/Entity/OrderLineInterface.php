<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Entity;

use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;

interface OrderLineInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getOrder(): ?OrderInterface;

    public function setOrder(?OrderInterface $order): static;

    public function getListing(): ?ListingInterface;

    public function setListing(?ListingInterface $listing): static;

    public function getTitleSnapshot(): string;

    public function setTitleSnapshot(string $v): static;

    public function getReferenceSnapshot(): string;

    public function setReferenceSnapshot(string $v): static;

    public function getQuantity(): int;

    public function setQuantity(int $v): static;

    public function getUnitPriceCents(): int;

    public function setUnitPriceCents(int $v): static;

    public function getCurrency(): CurrencyEnum;

    public function setCurrency(CurrencyEnum $v): static;

    public function getSubtotalCents(): int;
}
