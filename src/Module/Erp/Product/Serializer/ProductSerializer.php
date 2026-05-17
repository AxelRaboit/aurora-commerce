<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Serializer;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Media\Service\MediaUrlGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Ecommerce\Setting\EcommerceSettingEnum;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProductSerializerInterface::class)]
class ProductSerializer implements ProductSerializerInterface
{
    public function __construct(
        protected readonly SettingRepository $settingRepository,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    public function serialize(ProductInterface $product): array
    {
        $currency = $product->getCurrency();
        $priceCents = $product->getPriceCents();
        $price = null === $priceCents ? null : $priceCents / (10 ** $currency->decimals());

        $image = $product->getImage();
        $stockQuantity = $product->getStockQuantity();
        $threshold = (int) $this->settingRepository->getOrDefault(EcommerceSettingEnum::LowStockThreshold);
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
            'image' => $image instanceof MediaInterface ? [
                'id' => $image->getId(),
                'url' => $this->mediaUrlGenerator->publicUrl($image),
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
