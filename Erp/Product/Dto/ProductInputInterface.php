<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Dto;

use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;

interface ProductInputInterface
{
    public function getName(): string;

    public function getReference(): ?string;

    public function getDescription(): ?string;

    public function getPriceCents(): ?int;

    public function getCurrency(): CurrencyEnum;

    public function getStatus(): ProductStatusEnum;

    public function getType(): ProductTypeEnum;

    public function getImageId(): ?int;

    public function getStockQuantity(): ?int;
}
