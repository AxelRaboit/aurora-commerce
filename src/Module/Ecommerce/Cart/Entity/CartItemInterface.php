<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Entity;

use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Core\Money\Enum\CurrencyEnum;

interface CartItemInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getCart(): ?CartInterface;

    public function setCart(?CartInterface $cart): static;

    public function getListing(): ListingInterface;

    public function setListing(ListingInterface $listing): static;

    public function getQuantity(): int;

    public function setQuantity(int $quantity): static;

    public function getUnitPriceCents(): int;

    public function setUnitPriceCents(int $unitPriceCents): static;

    public function getCurrency(): CurrencyEnum;

    public function setCurrency(CurrencyEnum $currency): static;

    public function getSubtotalCents(): int;
}
