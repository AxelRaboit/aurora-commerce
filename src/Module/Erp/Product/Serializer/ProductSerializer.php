<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Serializer;

use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Erp\Product\Entity\Product;
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
            'reference' => $product->getReference(),
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
