<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Cart\Entity\CartItemInterface;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslation;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslationInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslation;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslationInterface;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Entity\OrderInterface;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Aurora\Module\Ecommerce\Order\Entity\OrderLineInterface;

/**
 * Self-contained bundle for the Ecommerce module. Ships with Erp in the
 * `aurora-commerce` package (cat. E) — a Listing wraps an Erp Product.
 *
 * @see AbstractAuroraModuleBundle
 */
final class AuroraEcommerceBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Ecommerce';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            CartInterface::class => Cart::class,
            CartItemInterface::class => CartItem::class,
            ListingInterface::class => Listing::class,
            ListingCategoryInterface::class => ListingCategory::class,
            ListingCategoryTranslationInterface::class => ListingCategoryTranslation::class,
            ListingTagInterface::class => ListingTag::class,
            ListingTagTranslationInterface::class => ListingTagTranslation::class,
            OrderInterface::class => Order::class,
            OrderLineInterface::class => OrderLine::class,
        ];
    }
}
