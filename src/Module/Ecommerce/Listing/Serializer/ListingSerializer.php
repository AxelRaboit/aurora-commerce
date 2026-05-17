<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Serializer;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Core\Media\Library\Service\MediaUrlGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Setting\EcommerceSettingEnum;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsAlias(ListingSerializerInterface::class)]
class ListingSerializer implements ListingSerializerInterface
{
    public function __construct(
        protected readonly SettingRepository $settingRepository,
        protected readonly RequestStack $requestStack,
        protected readonly LocaleContextInterface $localeContext,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    public function serialize(ListingInterface $listing): array
    {
        $product = $listing->getProduct();
        $currency = $product->getCurrency();
        $priceCents = $product->getPriceCents();
        $displayImage = $listing->getFeaturedImage() ?? $product->getImage();
        $stockQuantity = $product->getStockQuantity();
        $threshold = (int) $this->settingRepository->getOrDefault(EcommerceSettingEnum::LowStockThreshold);
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
                'url' => $this->mediaUrlGenerator->publicUrl($listing->getFeaturedImage()),
                'alt' => $listing->getFeaturedImage()->getAlt(),
            ] : null,
            'displayImage' => $displayImage instanceof MediaInterface ? [
                'id' => $displayImage->getId(),
                'url' => $this->mediaUrlGenerator->publicUrl($displayImage),
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
            'categories' => $this->serializeCategories($listing),
            'tags' => $this->serializeTags($listing),
            'createdAt' => $listing->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $listing->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    /** @return list<array{id: int|null, name: string, slug: string}> */
    private function serializeCategories(ListingInterface $listing): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request instanceof Request ? $request->getLocale() : $this->localeContext->getDefaultLocale();

        $result = [];
        foreach ($listing->getCategories() as $category) {
            $translation = $category->getTranslation($locale);
            if (null === $translation) {
                foreach ($category->getTranslations() as $candidate) {
                    $translation = $candidate;
                    break;
                }
            }

            if (null === $translation) {
                continue;
            }

            $result[] = [
                'id' => $category->getId(),
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
            ];
        }

        return $result;
    }

    /** @return list<array{id: int|null, name: string, slug: string, color: string}> */
    private function serializeTags(ListingInterface $listing): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request instanceof Request ? $request->getLocale() : $this->localeContext->getDefaultLocale();

        $result = [];
        foreach ($listing->getTags() as $tag) {
            $translation = $tag->getTranslation($locale);
            if (null === $translation) {
                foreach ($tag->getTranslations() as $candidate) {
                    $translation = $candidate;
                    break;
                }
            }

            if (null === $translation) {
                continue;
            }

            $result[] = [
                'id' => $tag->getId(),
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
                'color' => $tag->getColor(),
            ];
        }

        return $result;
    }
}
