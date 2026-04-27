<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Entity;

use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Order\Repository\OrderLineRepository;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderLineRepository::class)]
#[ORM\Table(name: 'ecommerce_order_lines')]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: Listing::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Listing $listing = null;

    #[ORM\Column(length: 200)]
    private string $titleSnapshot;

    #[ORM\Column(length: 64)]
    private string $skuSnapshot;

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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(?Listing $listing): static
    {
        $this->listing = $listing;

        return $this;
    }

    public function getTitleSnapshot(): string
    {
        return $this->titleSnapshot;
    }

    public function setTitleSnapshot(string $v): static
    {
        $this->titleSnapshot = $v;

        return $this;
    }

    public function getSkuSnapshot(): string
    {
        return $this->skuSnapshot;
    }

    public function setSkuSnapshot(string $v): static
    {
        $this->skuSnapshot = $v;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $v): static
    {
        $this->quantity = max(1, $v);

        return $this;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function setUnitPriceCents(int $v): static
    {
        $this->unitPriceCents = $v;

        return $this;
    }

    public function getCurrency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function setCurrency(CurrencyEnum $v): static
    {
        $this->currency = $v;

        return $this;
    }

    public function getSubtotalCents(): int
    {
        return $this->unitPriceCents * $this->quantity;
    }
}
