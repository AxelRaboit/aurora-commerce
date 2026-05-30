<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Entity;

use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Core\Money\Enum\CurrencyEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractOrderLine implements OrderLineInterface
{
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: OrderInterface::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?OrderInterface $order = null;

    #[ORM\ManyToOne(targetEntity: ListingInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?ListingInterface $listing = null;

    #[ORM\Column(length: 200)]
    protected string $titleSnapshot;

    #[ORM\Column(length: 64)]
    protected string $referenceSnapshot;

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

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(?OrderInterface $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getListing(): ?ListingInterface
    {
        return $this->listing;
    }

    public function setListing(?ListingInterface $listing): static
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

    public function getReferenceSnapshot(): string
    {
        return $this->referenceSnapshot;
    }

    public function setReferenceSnapshot(string $v): static
    {
        $this->referenceSnapshot = $v;

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
