<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Entity;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractProduct implements ProductInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 150)]
    protected string $name;

    #[ORM\Column(length: 64)]
    protected string $reference;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(nullable: true)]
    protected ?int $priceCents = null;

    #[ORM\Column(length: 3, enumType: CurrencyEnum::class, options: ['default' => 'EUR'])]
    protected CurrencyEnum $currency = CurrencyEnum::EUR;

    #[ORM\Column(length: 16, enumType: ProductStatusEnum::class)]
    protected ProductStatusEnum $status = ProductStatusEnum::Draft;

    #[ORM\Column(length: 16, enumType: ProductTypeEnum::class, options: ['default' => 'physical'])]
    protected ProductTypeEnum $type = ProductTypeEnum::Physical;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaInterface $image = null;

    #[ORM\Column(nullable: true)]
    protected ?int $stockQuantity = null;

    public function getImage(): ?MediaInterface
    {
        return $this->image;
    }

    public function setImage(?MediaInterface $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(?int $stockQuantity): static
    {
        $this->stockQuantity = null === $stockQuantity ? null : max(0, $stockQuantity);

        return $this;
    }

    public function isStockTracked(): bool
    {
        return null !== $this->stockQuantity;
    }

    public function isInStock(int $requestedQuantity = 1): bool
    {
        if (null === $this->stockQuantity) {
            return true;
        }

        return $this->stockQuantity >= $requestedQuantity;
    }

    public function decrementStock(int $quantity): void
    {
        if (null === $this->stockQuantity) {
            return;
        }

        $this->stockQuantity = max(0, $this->stockQuantity - $quantity);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriceCents(): ?int
    {
        return $this->priceCents;
    }

    public function setPriceCents(?int $priceCents): static
    {
        $this->priceCents = $priceCents;

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

    public function getStatus(): ProductStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ProductStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): ProductTypeEnum
    {
        return $this->type;
    }

    public function setType(ProductTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isPhysical(): bool
    {
        return $this->type->requiresShipping();
    }
}
