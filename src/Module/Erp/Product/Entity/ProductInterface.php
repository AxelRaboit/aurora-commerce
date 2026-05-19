<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Aurora\Module\Media\Library\Entity\MediaInterface;

interface ProductInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getImage(): ?MediaInterface;

    public function setImage(?MediaInterface $image): static;

    public function getStockQuantity(): ?int;

    public function setStockQuantity(?int $stockQuantity): static;

    public function isStockTracked(): bool;

    public function isInStock(int $requestedQuantity = 1): bool;

    public function decrementStock(int $quantity): void;

    public function getName(): string;

    public function setName(string $name): static;

    public function getReference(): string;

    public function setReference(string $reference): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getPriceCents(): ?int;

    public function setPriceCents(?int $priceCents): static;

    public function getCurrency(): CurrencyEnum;

    public function setCurrency(CurrencyEnum $currency): static;

    public function getStatus(): ProductStatusEnum;

    public function setStatus(ProductStatusEnum $status): static;

    public function getType(): ProductTypeEnum;

    public function setType(ProductTypeEnum $type): static;

    public function isPhysical(): bool;
}
