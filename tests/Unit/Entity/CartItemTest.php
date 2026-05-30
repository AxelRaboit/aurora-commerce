<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use PHPUnit\Framework\TestCase;

final class CartItemTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new CartItem())->getId());
    }

    public function testDefaultValues(): void
    {
        $item = new CartItem();

        self::assertNull($item->getReference());
        self::assertNull($item->getCart());
        self::assertSame(1, $item->getQuantity());
        self::assertSame(0, $item->getUnitPriceCents());
        self::assertSame(CurrencyEnum::EUR, $item->getCurrency());
    }

    public function testCartGetterAndSetter(): void
    {
        $cart = new Cart();
        $item = (new CartItem())->setCart($cart);

        self::assertSame($cart, $item->getCart());

        $item->setCart(null);
        self::assertNull($item->getCart());
    }

    public function testListingGetterAndSetter(): void
    {
        $listing = $this->createStub(ListingInterface::class);
        $item = (new CartItem())->setListing($listing);

        self::assertSame($listing, $item->getListing());
    }

    public function testQuantityGetterAndSetter(): void
    {
        $item = (new CartItem())->setQuantity(5);

        self::assertSame(5, $item->getQuantity());
    }

    public function testUnitPriceCentsGetterAndSetter(): void
    {
        $item = (new CartItem())->setUnitPriceCents(1999);

        self::assertSame(1999, $item->getUnitPriceCents());
    }

    public function testCurrencyGetterAndSetter(): void
    {
        $item = (new CartItem())->setCurrency(CurrencyEnum::USD);

        self::assertSame(CurrencyEnum::USD, $item->getCurrency());
    }

    public function testGetSubtotalCentsIsUnitPriceTimesQuantity(): void
    {
        $item = (new CartItem())->setUnitPriceCents(500)->setQuantity(3);

        self::assertSame(1500, $item->getSubtotalCents());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $item = (new CartItem())->setReference('CI-001');

        self::assertSame('CI-001', $item->getReference());
    }
}
