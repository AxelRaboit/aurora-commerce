<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Entity;

use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractCartItem implements CartItemInterface
{
    #[ORM\Column(length: 32, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: CartInterface::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?CartInterface $cart = null;

    #[ORM\ManyToOne(targetEntity: ListingInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ListingInterface $listing;

    #[ORM\Column]
    protected int $quantity = 1;

    #[ORM\Column]
    protected int $unitPriceCents = 0;

    #[ORM\Column(length: 3, enumType: CurrencyEnum::class)]
    protected CurrencyEnum $currency = CurrencyEnum::EUR;

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCart(): ?CartInterface
    {
        return $this->cart;
    }

    public function setCart(?CartInterface $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getListing(): ListingInterface
    {
        return $this->listing;
    }

    public function setListing(ListingInterface $listing): static
    {
        $this->listing = $listing;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, $quantity);

        return $this;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function setUnitPriceCents(int $unitPriceCents): static
    {
        $this->unitPriceCents = $unitPriceCents;

        return $this;
    }

    public function getCurrency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function setCurrency(CurrencyEnum $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getSubtotalCents(): int
    {
        return $this->unitPriceCents * $this->quantity;
    }
}
