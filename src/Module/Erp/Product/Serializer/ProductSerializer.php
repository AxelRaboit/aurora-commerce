<?php

declare(strict_types=1);

namespace App\Module\Erp\Product\Serializer;

use App\Core\Media\Entity\Media;
use App\Core\Setting\Enum\ApplicationParameterEnum;
use App\Core\Setting\Repository\SettingRepository;
use App\Module\Erp\Product\Entity\Product;
use DateTimeInterface;

final readonly class ProductSerializer
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function serialize(Product $product): array
    {
        $currency = $product->getCurrency();
        $priceCents = $product->getPriceCents();
        $price = null === $priceCents ? null : $priceCents / (10 ** $currency->decimals());

        $image = $product->getImage();
        $stockQuantity = $product->getStockQuantity();
        $threshold = (int) $this->settingRepository->getOrDefault(ApplicationParameterEnum::EcommerceLowStockThreshold);
        $isLowStock = $product->isStockTracked()
            && $threshold > 0
            && $stockQuantity > 0
            && $stockQuantity <= $threshold;

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'description' => $product->getDescription(),
            'price' => $price,
            'priceCents' => $priceCents,
            'currency' => $currency->value,
            'currencySymbol' => $currency->symbol(),
            'currencyDecimals' => $currency->decimals(),
            'status' => $product->getStatus()->value,
            'type' => $product->getType()->value,
            'requiresShipping' => $product->getType()->requiresShipping(),
            'image' => $image instanceof Media ? [
                'id' => $image->getId(),
                'url' => $image->getPublicUrl(),
                'alt' => $image->getAlt(),
            ] : null,
            'stockQuantity' => $stockQuantity,
            'stockTracked' => $product->isStockTracked(),
            'inStock' => $product->isInStock(),
            'isLowStock' => $isLowStock,
            'createdAt' => $product->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $product->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
