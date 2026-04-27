<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Entity;

use Aurora\Module\Ecommerce\Cart\Repository\CartItemRepository;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'ecommerce_cart_items')]
#[ORM\UniqueConstraint(name: 'uniq_ecommerce_cart_item_listing', columns: ['cart_id', 'listing_id'])]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(targetEntity: Listing::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Listing $listing;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column]
    private int $unitPriceCents = 0;

    #[ORM\Column(length: 3, enumType: CurrencyEnum::class)]
    private CurrencyEnum $currency = CurrencyEnum::EUR;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getListing(): Listing
    {
        return $this->listing;
    }

    public function setListing(Listing $listing): static
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
