<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Serializer;

use Aurora\Core\Media\Library\Service\MediaUrlGenerator;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartItemInterface;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CartSerializerInterface::class)]
class CartSerializer implements CartSerializerInterface
{
    public function __construct(
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    public function serialize(?CartInterface $cart): array
    {
        if (!$cart instanceof CartInterface) {
            return [];
        }

        $currency = CurrencyEnum::EUR;
        $items = [];
        foreach ($cart->getItems() as $item) {
            $currency = $item->getCurrency();
            $items[] = $this->serializeItem($item);
        }

        $totalCents = $cart->getTotalCents();

        return [
            'id' => $cart->getId(),
            'items' => $items,
            'totalQuantity' => $cart->getTotalQuantity(),
            'totalCents' => $totalCents,
            'total' => $totalCents / (10 ** $currency->decimals()),
            'currency' => $currency->value,
            'currencySymbol' => $currency->symbol(),
            'currencyDecimals' => $currency->decimals(),
        ];
    }

    private function serializeItem(CartItemInterface $item): array
    {
        $listing = $item->getListing();
        $featured = $listing->getFeaturedImage() ?? $listing->getProduct()->getImage();
        $unit = $item->getUnitPriceCents() / (10 ** $item->getCurrency()->decimals());
        $subtotal = $item->getSubtotalCents() / (10 ** $item->getCurrency()->decimals());

        return [
            'id' => $item->getId(),
            'listingId' => $listing->getId(),
            'slug' => $listing->getSlug(),
            'title' => $listing->getDisplayTitle(),
            'reference' => $listing->getProduct()->getReference(),
            'unitPrice' => $unit,
            'unitPriceCents' => $item->getUnitPriceCents(),
            'quantity' => $item->getQuantity(),
            'subtotal' => $subtotal,
            'subtotalCents' => $item->getSubtotalCents(),
            'currency' => $item->getCurrency()->value,
            'currencySymbol' => $item->getCurrency()->symbol(),
            'imageUrl' => $this->mediaUrlGenerator->publicUrl($featured),
        ];
    }
}
