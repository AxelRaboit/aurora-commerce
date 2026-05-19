<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Platform\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Cart())->getId());
    }

    public function testItemsCollectionInitialized(): void
    {
        self::assertCount(0, (new Cart())->getItems());
    }

    public function testDefaultValues(): void
    {
        $cart = new Cart();

        self::assertNull($cart->getReference());
        self::assertNull($cart->getSessionId());
        self::assertNull($cart->getUser());
    }

    public function testSessionIdGetterAndSetter(): void
    {
        $cart = (new Cart())->setSessionId('sess-abc');

        self::assertSame('sess-abc', $cart->getSessionId());
    }

    public function testUserGetterAndSetter(): void
    {
        $user = new User();
        $cart = (new Cart())->setUser($user);

        self::assertSame($user, $cart->getUser());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $cart = (new Cart())->setReference('CART-001');

        self::assertSame('CART-001', $cart->getReference());
    }

    private function makeItem(int $quantity, int $unitPriceCents): CartItem
    {
        $listing = $this->createStub(ListingInterface::class);

        return (new CartItem())->setListing($listing)->setQuantity($quantity)->setUnitPriceCents($unitPriceCents);
    }

    public function testAddItemAttachesCart(): void
    {
        $cart = new Cart();
        $item = $this->makeItem(2, 1000);

        $cart->addItem($item);

        self::assertCount(1, $cart->getItems());
        self::assertSame($cart, $item->getCart());
    }

    public function testAddItemIgnoresDuplicate(): void
    {
        $cart = new Cart();
        $item = $this->makeItem(1, 500);

        $cart->addItem($item);
        $cart->addItem($item);

        self::assertCount(1, $cart->getItems());
    }

    public function testRemoveItemDetachesCart(): void
    {
        $cart = new Cart();
        $item = $this->makeItem(1, 500);
        $cart->addItem($item);

        $cart->removeItem($item);

        self::assertCount(0, $cart->getItems());
        self::assertNull($item->getCart());
    }

    public function testGetTotalQuantitySumsItems(): void
    {
        $cart = new Cart();
        $cart->addItem($this->makeItem(2, 100));
        $cart->addItem($this->makeItem(3, 200));

        self::assertSame(5, $cart->getTotalQuantity());
    }

    public function testGetTotalCentsSumsSubtotals(): void
    {
        $cart = new Cart();
        $cart->addItem($this->makeItem(2, 1000));  // 2 * 1000 = 2000
        $cart->addItem($this->makeItem(3, 500));   // 3 * 500 = 1500

        self::assertSame(3500, $cart->getTotalCents());
    }
}
