<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Cart\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Cart\Manager\CartManager;
use Aurora\Module\Ecommerce\Cart\Repository\CartRepository;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Platform\User\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
final class CartManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;
    private Security $security;
    private RequestStack $requestStack;
    private SettingRepository $settingRepository;
    private CartManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cartRepository = $this->createMock(CartRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->settingRepository->method('getOrDefault')->willReturn('CART');

        // RequestStack with a real session (in-memory) — needed by the
        // anonymous-cart path that stashes a sessionId.
        $this->requestStack = new RequestStack();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack->push($request);

        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $this->manager = new CartManager(
            $this->entityManager,
            $this->cartRepository,
            $this->requestStack,
            $this->security,
            new SequenceGenerator($connection),
            $this->settingRepository,
        );
    }

    public function testGetCurrentCartReturnsExistingCartForLoggedUser(): void
    {
        $user = $this->makeUser(7);
        $existingCart = $this->makeCart();
        $existingCart->setUser($user);

        $this->security->method('getUser')->willReturn($user);
        $this->cartRepository->expects(self::once())
            ->method('findOneByUser')
            ->with($user)
            ->willReturn($existingCart);

        $this->entityManager->expects(self::never())->method('persist');

        self::assertSame($existingCart, $this->manager->getCurrentCart());
    }

    public function testGetCurrentCartCreatesCartForLoggedUserWhenMissing(): void
    {
        // First fetch returns null → manager persists a fresh cart bound
        // to the user, then stamps a sequence reference.
        $user = $this->makeUser(7);
        $this->security->method('getUser')->willReturn($user);
        $this->cartRepository->method('findOneByUser')->willReturn(null);

        $this->entityManager->expects(self::once())->method('persist')
            ->with(self::callback(static function (Cart $cart) use ($user): bool {
                self::assertSame($user, $cart->getUser());

                return true;
            }));

        $cart = $this->manager->getCurrentCart();

        self::assertSame($user, $cart->getUser());
        self::assertSame('CART-000001', $cart->getReference());
    }

    public function testGetCurrentCartReturnsNullForLoggedUserWhenCreateIfMissingFalse(): void
    {
        // The clear() flow doesn't want to materialize an empty cart
        // just to immediately delete it — pass false to skip the
        // create.
        $user = $this->makeUser();
        $this->security->method('getUser')->willReturn($user);
        $this->cartRepository->method('findOneByUser')->willReturn(null);

        $this->entityManager->expects(self::never())->method('persist');

        self::assertNull($this->manager->getCurrentCart(createIfMissing: false));
    }

    public function testGetCurrentCartUsesSessionForAnonymousVisitor(): void
    {
        // Anonymous flow: stash a random sessionId in the HTTP session,
        // bind the cart to it. Two requests from the same session find
        // the same cart.
        $this->security->method('getUser')->willReturn(null);

        $cart = $this->makeCart();
        $cart->setSessionId('test-session-id');
        $this->cartRepository->expects(self::once())
            ->method('findOneBySession')
            ->with(self::callback(static fn ($v): bool => is_string($v)))
            ->willReturn($cart);

        self::assertSame($cart, $this->manager->getCurrentCart());
    }

    public function testGetCurrentCartCreatesSessionBoundCartForAnonymous(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $this->cartRepository->method('findOneBySession')->willReturn(null);

        $this->entityManager->expects(self::once())->method('persist');

        $cart = $this->manager->getCurrentCart();

        self::assertNotNull($cart->getSessionId(), 'sessionId stashed via RequestStack/session');
        self::assertSame('CART-000001', $cart->getReference());
    }

    public function testAddItemCreatesNewItemFromListingProductPriceAndCurrency(): void
    {
        // First addItem on a listing → create a CartItem, copy
        // listing.product price/currency snapshot. Quantity defaults
        // to max(1, $input) (no zero/negative items).
        $cart = $this->primeCartForLoggedUser();

        $listing = $this->makeListing(id: 10, priceCents: 1250, currency: CurrencyEnum::EUR);

        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->addItem($listing, quantity: 3);

        self::assertCount(1, $cart->getItems());
        $item = $cart->getItems()->first();
        self::assertSame($listing, $item->getListing());
        self::assertSame(3, $item->getQuantity());
        self::assertSame(1250, $item->getUnitPriceCents(), 'price snapshotted from product, not lazy-loaded');
        self::assertSame(CurrencyEnum::EUR, $item->getCurrency());
    }

    public function testAddItemIncrementsQuantityWhenSameListingAddedAgain(): void
    {
        // Second addItem with same listing → bump existing quantity,
        // don't create a duplicate row.
        $cart = $this->primeCartForLoggedUser();
        $listing = $this->makeListing(id: 10);
        $existing = $this->makeCartItem(listing: $listing, quantity: 2);
        $cart->addItem($existing);

        $this->manager->addItem($listing, quantity: 3);

        self::assertCount(1, $cart->getItems(), 'no duplicate row — quantity bumped on the existing one');
        self::assertSame(5, $existing->getQuantity(), '2 + 3 = 5');
    }

    public function testAddItemClampsZeroOrNegativeQuantityToOne(): void
    {
        // Defensive: clients can't accidentally add 0 or negative
        // quantities (e.g. a faulty front spinner). Always at least 1.
        $cart = $this->primeCartForLoggedUser();
        $listing = $this->makeListing(id: 10);

        $this->manager->addItem($listing, quantity: 0);
        $this->manager->addItem($listing, quantity: -5);

        // First call: max(1, 0) = 1. Second call: existing+max(1,-5)=1+1=2.
        self::assertSame(2, $cart->getItems()->first()->getQuantity());
    }

    public function testUpdateItemQuantityRemovesItemWhenQuantityZeroOrLess(): void
    {
        // Quantity dropped to ≤ 0 is the canonical "remove from cart"
        // signal (matches the +/− stepper that lands on 0).
        $cart = $this->primeCartForLoggedUser();
        $listing = $this->makeListing(id: 10);
        $item = $this->makeCartItem(listing: $listing, quantity: 3);
        $cart->addItem($item);

        $this->entityManager->expects(self::once())->method('remove')->with($item);

        $this->manager->updateItemQuantity($listing, 0);

        self::assertFalse($cart->getItems()->contains($item));
    }

    public function testUpdateItemQuantityWritesNewQuantity(): void
    {
        $cart = $this->primeCartForLoggedUser();
        $listing = $this->makeListing(id: 10);
        $item = $this->makeCartItem(listing: $listing, quantity: 3);
        $cart->addItem($item);

        $this->manager->updateItemQuantity($listing, 7);

        self::assertSame(7, $item->getQuantity());
    }

    public function testRemoveItemDelegatesToUpdateItemQuantityWithZero(): void
    {
        // removeItem is a thin sugar — equivalent to updateItemQuantity(0).
        $cart = $this->primeCartForLoggedUser();
        $listing = $this->makeListing(id: 10);
        $item = $this->makeCartItem(listing: $listing, quantity: 1);
        $cart->addItem($item);

        $this->entityManager->expects(self::once())->method('remove')->with($item);

        $this->manager->removeItem($listing);

        self::assertFalse($cart->getItems()->contains($item));
    }

    public function testClearRemovesEveryItem(): void
    {
        $cart = $this->primeCartForLoggedUser();
        $a = $this->makeCartItem(listing: $this->makeListing(id: 1), quantity: 1);
        $b = $this->makeCartItem(listing: $this->makeListing(id: 2), quantity: 2);
        $cart->addItem($a);
        $cart->addItem($b);

        $this->entityManager->expects(self::exactly(2))->method('remove');

        $this->manager->clear();

        self::assertCount(0, $cart->getItems());
    }

    public function testClearOnAbsentCartIsNoop(): void
    {
        // No cart exists for this user → clear() must not create one
        // just to empty it (would be wasteful + confuse the create-on-
        // demand UX).
        $user = $this->makeUser();
        $this->security->method('getUser')->willReturn($user);
        $this->cartRepository->method('findOneByUser')->willReturn(null);

        $this->entityManager->expects(self::never())->method('remove');
        $this->entityManager->expects(self::never())->method('flush');

        $this->manager->clear();
    }

    // ── Fixtures ────────────────────────────────────────────────────

    private function primeCartForLoggedUser(): Cart
    {
        $user = $this->makeUser();
        $cart = $this->makeCart();
        $cart->setUser($user);
        $this->security->method('getUser')->willReturn($user);
        $this->cartRepository->method('findOneByUser')->willReturn($cart);

        return $cart;
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function makeCart(int $id = 1): Cart
    {
        $cart = new Cart();
        (new ReflectionProperty(Cart::class, 'id'))->setValue($cart, $id);

        return $cart;
    }

    private function makeListing(int $id, int $priceCents = 1000, CurrencyEnum $currency = CurrencyEnum::EUR): Listing
    {
        $product = new Product();
        $product->setPriceCents($priceCents);
        $product->setCurrency($currency);

        $listing = new Listing();
        (new ReflectionProperty(Listing::class, 'id'))->setValue($listing, $id);
        $listing->setProduct($product);

        return $listing;
    }

    private function makeCartItem(Listing $listing, int $quantity): CartItem
    {
        $item = new CartItem();
        $item->setListing($listing);
        $item->setQuantity($quantity);
        $item->setUnitPriceCents(1000);
        $item->setCurrency(CurrencyEnum::EUR);

        return $item;
    }
}
