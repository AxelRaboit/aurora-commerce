<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Serializer;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ListingSerializerInterface::class)]
class ListingSerializer implements ListingSerializerInterface
{
    public function __construct(protected readonly SettingRepository $settingRepository) {}

    public function serialize(ListingInterface $listing): array
    {
        $product = $listing->getProduct();
        $currency = $product->getCurrency();
        $priceCents = $product->getPriceCents();
        $displayImage = $listing->getFeaturedImage() ?? $product->getImage();
        $stockQuantity = $product->getStockQuantity();
        $threshold = (int) $this->settingRepository->getOrDefault(ApplicationParameterEnum::EcommerceLowStockThreshold);
        $isLowStock = $product->isStockTracked()
            && $threshold > 0
            && $stockQuantity > 0
            && $stockQuantity <= $threshold;

        return [
            'id' => $listing->getId(),
            'slug' => $listing->getSlug(),
            'displayTitle' => $listing->getDisplayTitle(),
            'marketingTitle' => $listing->getMarketingTitle(),
            'marketingDescription' => $listing->getMarketingDescription(),
            'isVisibleOnShop' => $listing->isVisibleOnShop(),
            'seoTitle' => $listing->getSeoTitle(),
            'seoDescription' => $listing->getSeoDescription(),
            'featuredImage' => $listing->getFeaturedImage() instanceof MediaInterface ? [
                'id' => $listing->getFeaturedImage()->getId(),
                'url' => $listing->getFeaturedImage()->getPublicUrl(),
                'alt' => $listing->getFeaturedImage()->getAlt(),
            ] : null,
            'displayImage' => $displayImage instanceof MediaInterface ? [
                'id' => $displayImage->getId(),
                'url' => $displayImage->getPublicUrl(),
                'alt' => $displayImage->getAlt(),
            ] : null,
            'product' => [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'reference' => $product->getReference(),
                'price' => null === $priceCents ? null : $priceCents / (10 ** $currency->decimals()),
                'priceCents' => $priceCents,
                'currency' => $currency->value,
                'currencySymbol' => $currency->symbol(),
                'status' => $product->getStatus()->value,
                'type' => $product->getType()->value,
                'requiresShipping' => $product->getType()->requiresShipping(),
                'stockQuantity' => $stockQuantity,
                'stockTracked' => $product->isStockTracked(),
                'inStock' => $product->isInStock(),
                'isLowStock' => $isLowStock,
            ],
            'createdAt' => $listing->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $listing->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
