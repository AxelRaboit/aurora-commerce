<?php

declare(strict_types=1);

namespace App\Module\Erp\Product\Entity;

use App\Core\Media\Entity\Media;
use App\Core\Trait\TimestampableTrait;
use App\Module\Erp\Product\Enum\CurrencyEnum;
use App\Module\Erp\Product\Enum\ProductStatusEnum;
use App\Module\Erp\Product\Enum\ProductTypeEnum;
use App\Module\Erp\Product\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'erp_products')]
#[ORM\UniqueConstraint(name: 'uniq_erp_product_sku', columns: ['sku'])]
#[ORM\HasLifecycleCallbacks]
class Product
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 64)]
    private string $sku;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $priceCents = null;

    #[ORM\Column(length: 3, enumType: CurrencyEnum::class, options: ['default' => 'EUR'])]
    private CurrencyEnum $currency = CurrencyEnum::EUR;

    #[ORM\Column(length: 16, enumType: ProductStatusEnum::class)]
    private ProductStatusEnum $status = ProductStatusEnum::Draft;

    #[ORM\Column(length: 16, enumType: ProductTypeEnum::class, options: ['default' => 'physical'])]
    private ProductTypeEnum $type = ProductTypeEnum::Physical;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Media $image = null;

    #[ORM\Column(nullable: true)]
    private ?int $stockQuantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?Media
    {
        return $this->image;
    }

    public function setImage(?Media $image): static
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

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

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
