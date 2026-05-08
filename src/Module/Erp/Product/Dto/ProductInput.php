<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Dto;

use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class ProductInput implements ProductInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'erp.products.errors.name_required')]
        #[Assert\Length(max: 150)]
        public readonly string $name = '',
        #[Assert\Length(max: 64)]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9_-]+$/', message: 'erp.products.errors.reference_invalid')]
        public readonly ?string $reference = null,
        public readonly ?string $description = null,
        #[Assert\PositiveOrZero(message: 'erp.products.errors.price_invalid')]
        public readonly ?int $priceCents = null,
        public readonly CurrencyEnum $currency = CurrencyEnum::EUR,
        public readonly ProductStatusEnum $status = ProductStatusEnum::Draft,
        public readonly ProductTypeEnum $type = ProductTypeEnum::Physical,
        public readonly ?int $imageId = null,
        #[Assert\PositiveOrZero(message: 'erp.products.errors.stock_invalid')]
        public readonly ?int $stockQuantity = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPriceCents(): ?int
    {
        return $this->priceCents;
    }

    public function getCurrency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function getStatus(): ProductStatusEnum
    {
        return $this->status;
    }

    public function getType(): ProductTypeEnum
    {
        return $this->type;
    }

    public function getImageId(): ?int
    {
        return $this->imageId;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }
}
